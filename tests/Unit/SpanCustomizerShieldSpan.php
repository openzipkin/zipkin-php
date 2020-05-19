<?php

namespace ZipkinTests\Unit;

use LogicException;
use Zipkin\Endpoint;
use Zipkin\Propagation\TraceContext;
use PHPUnit\Framework\TestCase;

trait SpanCustomizerShieldSpan
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
        return false;
    }

    public function getContext(): TraceContext
    {
        return $this->context;
    }

    public function start(int $timestamp = null): void
    {
        throw new LogicException('should not be called');
    }

    public function setKind(string $kind): void
    {
        throw new LogicException('should not be called');
    }

    public function setRemoteEndpoint(Endpoint $remoteEndpoint): void
    {
        throw new LogicException('should not be called');
    }

    public function abandon(): void
    {
        throw new LogicException('should not be called');
    }

    public function finish(int $timestamp = null): void
    {
        throw new LogicException('should not be called');
    }

    public function flush(): void
    {
        throw new LogicException('should not be called');
    }
}
