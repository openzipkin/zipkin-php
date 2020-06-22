<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Zipkin\Tracing;

class ClientTracing
{
    private $tracing;
    private $parser;
    private $requestSampler;

    public function __construct(
        Tracing $tracing,
        Parser $parser = null,
        callable $requestSampler = null
    ) {
        $this->tracing = $tracing;
        $this->parser = $parser ?? new DefaultParser;
        $this->requestSampler = $requestSampler ?? static function (): ?bool {
            return null;
        };
    }

    public function getTracing(): Tracing
    {
        return $this->tracing;
    }

    /**
     * @return callable with the signature
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
