<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client\Psr18;

use Zipkin\Tracer;
use Zipkin\SpanCustomizerShield;
use Zipkin\Propagation\TraceContext;
use Zipkin\Kind;
use Zipkin\Instrumentation\Http\Client\Parser;
use Zipkin\Instrumentation\Http\Client\HttpClientTracing;
use Throwable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Client\ClientInterface;

final class Client implements ClientInterface
{
    /**
     * @var ClientInterface
     */
    private $delegate;

    /**
     * @var callable(TraceContext,mixed):void
     */
    private $injector;

    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var (callable(RequestInterface):?bool)|null
     */
    private $requestSampler;

    public function __construct(ClientInterface $client, HttpClientTracing $tracing)
    {
        $this->delegate = $client;
        $this->injector = $tracing->getTracing()->getPropagation()->getInjector(new RequestHeaders());
        $this->tracer = $tracing->getTracing()->getTracer();
        $this->parser = $tracing->getParser();
        $this->requestSampler = $tracing->getRequestSampler();
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($this->requestSampler === null) {
            $span = $this->tracer->nextSpan();
        } else {
            $span = $this->tracer->nextSpanWithSampler(
                $this->requestSampler,
                [$request]
            );
        }

        ($this->injector)($span->getContext(), $request);

        if ($span->isNoop()) {
            try {
                return $this->delegate->sendRequest($request);
            } finally {
                $span->finish();
            }
        }

        $span->setKind(Kind\CLIENT);
        // If span is NOOP it does not make sense to add customizations
        // to it like tags or annotations.
        $spanCustomizer = new SpanCustomizerShield($span);
        $span->setName($this->parser->spanName($request));
        $this->parser->request($request, $span->getContext(), $spanCustomizer);

        try {
            $span->start();
            $response = $this->delegate->sendRequest($request);
            $this->parser->response($response, $span->getContext(), $spanCustomizer);
            return $response;
        } catch (Throwable $e) {
            $span->setError($e);
            throw $e;
        } finally {
            $span->finish();
        }
    }
}
