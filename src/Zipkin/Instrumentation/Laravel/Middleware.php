<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Laravel;

use Zipkin\Tracing;
use Zipkin\Tracer;
use Zipkin\SpanCustomizerShield;
use Zipkin\Span;
use Zipkin\Propagation\TraceContext;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Kind;
use Zipkin\Instrumentation\Laravel\Propagation\RequestHeaders;
use Zipkin\Instrumentation\Http\Server\HttpServerTracing;
use Throwable;
use Illuminate\Http\Request;
use Closure;

class Middleware
{
    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * @var callable(array):?bool
     */
    private $extractor;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var (callable(Request):?bool)|null
     */
    private $requestSampler;

    public function __construct(HttpServerTracing $tracing)
    {
        $this->tracer = $tracing->getTracing()->getTracer();
        $this->extractor = $tracing->getTracing()->getPropagation()->getExtractor(new RequestHeaders());
        $this->parser = $tracing->getParser();
        $this->requestSampler = $tracing->getRequestSampler();
    }

    public static function createFromTracing(Tracing $tracing): self
    {
        return new self(new HttpServerTracing($tracing, new DefaultParser));
    }

    public function handle(Request $request, Closure $next)
    {
        $extractedContext = ($this->extractor)($request);

        $span = $this->nextSpan($extractedContext, $request);
        $scopeCloser = $this->tracer->openScope($span);

        if ($span->isNoop()) {
            try {
                return $next($request);
            } finally {
                $span->finish();
                $scopeCloser();
            }
        }

        $span->setKind(Kind\SERVER);
        $spanCustomizer = new SpanCustomizerShield($span);
        $span->setName($this->parser->spanName($request));
        $this->parser->request($request, $span->getContext(), $spanCustomizer);

        try {
            $response = $next($request);
            $this->parser->response($response, $span->getContext(), $spanCustomizer);
            return $response;
        } catch (Throwable $e) {
            $span->setError($e);
            throw $e;
        } finally {
            $span->finish();
            $scopeCloser();
        }
    }

    private function nextSpan(?SamplingFlags $extractedContext, Request $request): Span
    {
        if ($extractedContext instanceof TraceContext) {
            return $this->tracer->joinSpan($extractedContext);
        }

        $extractedContext = $extractedContext ?? DefaultSamplingFlags::createAsEmpty();
        if ($this->requestSampler === null) {
            return $this->tracer->nextSpan($extractedContext);
        }

        return $this->tracer->nextSpanWithSampler(
            $this->requestSampler,
            [$request],
            $extractedContext
        );
    }
}
