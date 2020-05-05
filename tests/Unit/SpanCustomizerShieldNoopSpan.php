<?php

namespace ZipkinTests\Unit;

use Zipkin\Endpoint;
use Zipkin\Propagation\TraceContext;
use PHPUnit\Framework\TestCase;

trait SpanCustomizerShieldNoopSpan
{
    /**
     * @var TestCase
     */
    private $test;

    /**
     * @var TraceContext
     */
    private $context;

    public function __construct(TestCase $test)
    {
        $this->test = $test;
        $this->context = TraceContext::createAsRoot();
    }

    public function isNoop(): bool
    {
        return true;
    }

    public function getContext(): TraceContext
    {
        return $this->context;
    }

    public function start(int $timestamp = null): void
    {
    }

    public function setKind(string $kind): void
    {
    }

    public function setRemoteEndpoint(Endpoint $remoteEndpoint): void
    {
    }

    public function abandon(): void
    {
    }

    public function finish(int $timestamp = null): void
    {
    }

    public function flush(): void
    {
    }
}
