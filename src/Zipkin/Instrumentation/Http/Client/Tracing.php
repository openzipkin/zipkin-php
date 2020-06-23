<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Zipkin\Tracing as BaseTracing;
use Psr\Http\Message\RequestInterface;

/**
 * Tracing includes all the elements needed to trace and HTTP client
 */
class Tracing
{
    /**
     * @var BaseTracing
     */
    private $tracing;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var callable
     *
     * function that decides to sample or not an unsampled
     * request. The signature is:
     *
     * <pre>
     * function (RequestInterface $request): ?bool {}
     * </pre>
     */
    private $requestSampler;

    public function __construct(
        BaseTracing $tracing,
        Parser $parser = null,
        callable $requestSampler = null
    ) {
        $this->tracing = $tracing;
        $this->parser = $parser ?? new DefaultParser;
        $this->requestSampler = $requestSampler ?? static function (RequestInterface $request): ?bool {
            return null;
        };
    }

    public function getTracing(): BaseTracing
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
    public function getRequestSampler(): callable
    {
        return $this->requestSampler;
    }

    public function getParser(): Parser
    {
        return $this->parser;
    }
}
