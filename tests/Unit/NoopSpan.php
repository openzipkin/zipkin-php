<?php

namespace ZipkinTests\Unit;

use Zipkin\Endpoint;
use Zipkin\Propagation\TraceContext;
use PHPUnit\Framework\TestCase;

trait NoopSpan
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

    public function setName(string $name): void
    {
        $this->test->assertEquals(SpanCustomizerShieldTest::TEST_NAME, $name);
    }

    public function setKind(string $kind): void
    {
    }
    public function tag(string $key, string $value): void
    {
        $this->test->assertEquals(SpanCustomizerShieldTest::TEST_TAG_KEY, $key);
        $this->test->assertEquals(SpanCustomizerShieldTest::TEST_TAG_VALUE, $value);
    }
    public function annotate(string $value, int $timestamp = null): void
    {
        $this->test->assertEquals(SpanCustomizerShieldTest::TEST_ANNOTATION_VALUE, $value);
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
};
