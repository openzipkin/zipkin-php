<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Zipkin\Tracing;

/**
 * ClientTracing includes all the elements needed to instrument a
 * HTTP client.
 */
class ClientTracing
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
     * request. The signature is:
     *
     * <pre>
     * function (RequestInterface $request): ?bool {}
     * </pre>
     *
     * @var callable|null
     */
    private $requestSampler;

    public function __construct(
        Tracing $tracing,
        Parser $parser = null,
        callable $requestSampler = null
    ) {
        $this->tracing = $tracing;
        $this->parser = $parser ?? new DefaultParser;
        $this->requestSampler = $requestSampler;
    }

    public function getTracing(): Tracing
    {
        return $this->tracing;
    }

    /**
     * @return callable with the signature:
     *
     * <pre>
     * function (RequestInterface $request): ?bool
     * </pre>
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
