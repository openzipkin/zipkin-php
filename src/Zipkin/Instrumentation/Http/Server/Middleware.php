<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server;

use Zipkin\Tracer;
use Zipkin\SpanCustomizerShield;
use Zipkin\Propagation\TraceContext;
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
    private $requestSampler;

    public function __construct(ServerTracing $tracing)
    {
        $this->tracer = $tracing->getTracing()->getTracer();
        $this->extractor = $tracing->getTracing()->getPropagation()->getExtractor(new RequestHeaders());
        $this->parser = $tracing->getParser();
        $this->requestSampler = $tracing->getRequestSampler();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $extractedContext = ($this->extractor)($request);

        if ($extractedContext instanceof TraceContext) {
            $span = $this->tracer->joinSpan($extractedContext);
        } elseif ($this->requestSampler === null) {
            $span = $this->tracer->nextSpan($extractedContext);
        } else {
            $span = $this->tracer->nextSpanWithSampler(
                $this->requestSampler,
                [$request],
                $extractedContext
            );
        }
        $spanCustomizer = null;
        if (!$span->isNoop()) {
            $span->setKind(Kind\SERVER);
            // If span is NOOP it does not make sense to add customizations
            // to it like tags or annotations.
            $spanCustomizer = new SpanCustomizerShield($span);
            $span->setName($this->parser->spanName($request));
            $this->parser->request($request, $span->getContext(), $spanCustomizer);
        }

        try {
            $response = $handler->handle($request);
            if ($spanCustomizer !== null) {
                $this->parser->response($response, $span->getContext(), $spanCustomizer);
            }
            return $response;
        } catch (Throwable $e) {
            if ($spanCustomizer !== null) {
                $this->parser->error($e, $span->getContext(), $spanCustomizer);
            }
            throw $e;
        } finally {
            $span->finish();
        }
    }
}