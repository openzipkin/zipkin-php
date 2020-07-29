<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server\Psr15;

use Zipkin\Tracer;
use Zipkin\SpanCustomizerShield;
use Zipkin\Span;
use Zipkin\Propagation\TraceContext;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Kind;
use Zipkin\Instrumentation\Http\Server\Request as ServerRequest;
use Zipkin\Instrumentation\Http\Server\Psr15\Propagation\RequestHeaders;
use Zipkin\Instrumentation\Http\Server\Parser;
use Zipkin\Instrumentation\Http\Server\HttpServerTracing;
use Throwable;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Middleware implements MiddlewareInterface
{
    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * @var callable(ServerRequestInterface):SamplingFlags
     */
    private $extractor;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var (callable(ServerRequest):?bool)|null
     */
    private $requestSampler;

    public function __construct(HttpServerTracing $tracing)
    {
        $this->tracer = $tracing->getTracing()->getTracer();
        $this->extractor = $tracing->getTracing()->getPropagation()->getExtractor(new RequestHeaders());
        $this->parser = $tracing->getParser();
        $this->requestSampler = $tracing->getRequestSampler();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $extractedContext = ($this->extractor)($request);

        $span = $this->nextSpan($extractedContext, $request);
        $scopeCloser = $this->tracer->openScope($span);

        if ($span->isNoop()) {
            try {
                return $handler->handle($request);
            } finally {
                $span->finish();
                $scopeCloser();
            }
        }

        $parsedRequest = new Request($request);

        $span->setKind(Kind\SERVER);
        $spanCustomizer = new SpanCustomizerShield($span);
        $this->parser->request($parsedRequest, $span->getContext(), $spanCustomizer);

        try {
            $response = $handler->handle($request);
            $this->parser->response(new Response($response, $parsedRequest), $span->getContext(), $spanCustomizer);
            return $response;
        } catch (Throwable $e) {
            $span->setError($e);
            throw $e;
        } finally {
            $span->finish();
            $scopeCloser();
        }
    }

    private function nextSpan(?SamplingFlags $extractedContext, ServerRequestInterface $request): Span
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
