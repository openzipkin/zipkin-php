<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Throwable;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zipkin\Tags;
use Zipkin\Tracer;
use Zipkin\Tracing;
use Zipkin\Instrumentation\Http\TraceContextExtractor;
use Zipkin\Instrumentation\Http\Client\Tracing as ClientHandler;
use Zipkin\Propagation\TraceContext;
use Zipkin\Propagation\RequestHeaders;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\SpanCustomizerShield;

final class Client implements ClientInterface
{
    /**
     * @var ClientInterface
     */
    private $delegate;

    /**
     * @var callable
     */
    private $injector;

    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * @var Handler
     */
    private $handler;

    public function __construct(
        ClientInterface $delegate,
        Tracing $tracing,
        Handler $handler = null
    ) {
        $this->delegate = $delegate;
        $this->injector = $tracing->getPropagation()->getInjector(new RequestHeaders());
        $this->tracer = $tracing->getTracer();
        $this->handler = $handler ?? new DefaultHandler;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $span = $this->tracer->nextSpanWithSampler(
            [$this->handler, 'requestSampler'],
            [$request]
        );

        $spanCustomizer = null;
        if (!$span->isNoop()) {
            // If span is NOOP it does not make sense to add customizations
            // to it like tags or annotations.
            $spanCustomizer = new SpanCustomizerShield($span);
            $span->setName($this->handler->spanName($request));
            $this->handler->parseRequest($request, $span->getContext(), $spanCustomizer);
        }

        ($this->injector)($span->getContext(), $request);
        try {
            $span->start();
            $response = $this->delegate->sendRequest($request);
            if ($spanCustomizer !== null) {
                $this->handler->parseResponse($response, $span->getContext(), $spanCustomizer);
            }
            return $response;
        } catch (Throwable $e) {
            if ($spanCustomizer !== null) {
                $this->handler->parseError($e, $span->getContext(), $spanCustomizer);
            }
            throw $e;
        } finally {
            $span->finish();
        }
    }
}
