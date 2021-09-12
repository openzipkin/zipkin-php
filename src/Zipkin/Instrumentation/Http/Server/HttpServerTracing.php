<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server;

use Zipkin\Tracing;

/**
 * HttpServerTracing includes all the elements needed to trace a HTTP server
 * middleware or request handler.
 */
class HttpServerTracing
{
    private Tracing $tracing;

    private HttpServerParser $parser;

    /**
     * function that decides to sample or not an unsampled
     * request.
     *
     * @var (callable(Request):?bool)|null
     */
    private $requestSampler;

    public function __construct(
        Tracing $tracing,
        HttpServerParser $parser = null,
        callable $requestSampler = null
    ) {
        $this->tracing = $tracing;
        $this->parser = $parser ?? new DefaultHttpServerParser();
        $this->requestSampler = $requestSampler;
    }

    public function getTracing(): Tracing
    {
        return $this->tracing;
    }

    /**
     * @return (callable(Request):?bool)|null
     */
    public function getRequestSampler(): ?callable
    {
        return $this->requestSampler;
    }

    public function getParser(): HttpServerParser
    {
        return $this->parser;
    }
}
