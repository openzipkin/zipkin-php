<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Zipkin\Tracer;
use Zipkin\SpanCustomizerShield;
use Zipkin\Kind;
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
     * @var callable
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
     * @var callable|null
     */
    private $requestSampler;

    public function __construct(
        ClientInterface $delegate,
        ClientTracing $tracing
    ) {
        $this->delegate = $delegate;
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

        $spanCustomizer = null;
        if (!$span->isNoop()) {
            $span->setKind(Kind\CLIENT);
            // If span is NOOP it does not make sense to add customizations
            // to it like tags or annotations.
            $spanCustomizer = new SpanCustomizerShield($span);
            $span->setName($this->parser->spanName($request));
            $this->parser->request($request, $span->getContext(), $spanCustomizer);
        }

        ($this->injector)($span->getContext(), $request);
        try {
            $span->start();
            $response = $this->delegate->sendRequest($request);
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
