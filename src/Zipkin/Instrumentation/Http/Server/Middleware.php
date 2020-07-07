<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server;

use Zipkin\Tracer;
use Zipkin\SpanCustomizerShield;
use Zipkin\Kind;
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
     * @var callable
     */
    private $nextSpanHandler;

    public function __construct(ServerTracing $tracing)
    {
        $this->tracer = $tracing->getTracing()->getTracer();
        $this->extractor = $tracing->getTracing()->getPropagation()->getExtractor(new RequestHeaders());
        $this->parser = $tracing->getParser();
        $this->nextSpanHandler = $tracing->getNextSpanHandler();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $extractedContext = ($this->extractor)($request);

        $span = ($this->nextSpanHandler)($extractedContext, $request);

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
