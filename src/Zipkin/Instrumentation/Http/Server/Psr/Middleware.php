<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server\Psr;

use Zipkin\Tracer;
use Zipkin\SpanCustomizerShield;
use Zipkin\Span;
use Zipkin\Kind;
use Zipkin\Instrumentation\Http\Server\ServerTracing;
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
     * @var callable
     */
    private $extractor;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var callable(SamplingFlags,mixed):Span
     */
    private $nextSpanResolver;

    public function __construct(ServerTracing $tracing)
    {
        $this->tracer = $tracing->getTracing()->getTracer();
        $this->extractor = $tracing->getTracing()->getPropagation()->getExtractor(new RequestHeaders());
        $this->parser = $tracing->getParser();
        $this->nextSpanResolver = $tracing->getNextSpanResolver();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $extractedContext = ($this->extractor)($request);

        /**
         * @var Span $span
         */
        $span = ($this->nextSpanResolver)($extractedContext, $request);

        $scopeCloser = $this->tracer->openScope($span);

        if ($span->isNoop()) {
            try {
                return $handler->handle($request);
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
            $response = $handler->handle($request);
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
}
