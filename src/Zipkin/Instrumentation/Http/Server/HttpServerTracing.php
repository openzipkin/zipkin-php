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
    /**
     * @var Tracing
     */
    private $tracing;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * function that decides to sample or not an unsampled
     * request.
     *
     * @var (callable(mixed):?bool)|null
     */
    private $requestSampler;

    public function __construct(
        Tracing $tracing,
        Parser $parser,
        callable $requestSampler = null
    ) {
        $this->tracing = $tracing;
        $this->parser = $parser;
        $this->requestSampler = $requestSampler;
    }

    public function getTracing(): Tracing
    {
        return $this->tracing;
    }

    /**
     * @return (callable(mixed):?bool)|null
     */
    public function getRequestSampler(): ?callable
    {
        return $this->requestSampler;
    }

    public function getParser(): Parser
    {
        return $this->parser;
    }
}
