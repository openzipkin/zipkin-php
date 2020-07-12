<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client\Psr;

use Zipkin\SpanCustomizerShield;
use Zipkin\Span;
use Zipkin\Propagation\TraceContext;
use Zipkin\Kind;
use Zipkin\Instrumentation\Http\Client\Parser;
use Zipkin\Instrumentation\Http\Client\ClientTracing;
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
     * @var Parser
     */
    private $parser;

    /**
     * @var callable(mixed):Span
     */
    private $nextSpanResolver;

    public function __construct(
        ClientInterface $delegate,
        ClientTracing $tracing
    ) {
        $this->delegate = $delegate;
        $this->injector = $tracing->getTracing()->getPropagation()->getInjector(new RequestHeaders());
        $this->parser = $tracing->getParser();
        $this->nextSpanResolver = $tracing->getNextSpanResolver();
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        /**
         * @var Span $span
         */
        $span = ($this->nextSpanResolver)($request);
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
