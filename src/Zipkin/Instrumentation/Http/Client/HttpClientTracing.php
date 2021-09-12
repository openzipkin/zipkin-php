<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Zipkin\Tracing;
use Zipkin\Instrumentation\Http\Request;

/**
 * ClientTracing includes all the elements needed to instrument a
 * HTTP client.
 */
class HttpClientTracing
{
    private Tracing $tracing;

    private HttpClientParser $parser;

    /**
     * function that decides to sample or not an unsampled
     * request.
     *
     * @var callable(Request):?bool|null
     */
    private $requestSampler;

    public function __construct(
        Tracing $tracing,
        HttpClientParser $parser = null,
        callable $requestSampler = null
    ) {
        $this->tracing = $tracing;
        $this->parser = $parser ?? new DefaultHttpClientParser();
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

    public function getParser(): HttpClientParser
    {
        return $this->parser;
    }
}
