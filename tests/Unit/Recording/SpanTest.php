<?php

namespace ZipkinTests\Unit\Recording;

use Zipkin\Recording\Span;
use Zipkin\Propagation\TraceContext;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Kind;
use Zipkin\Endpoint;
use PHPUnit\Framework\TestCase;
use function Zipkin\Timestamp\now;

final class SpanTest extends TestCase
{
    public function testCreateSpanAsRootSuccess()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $endpoint = Endpoint::createAsEmpty();
        $span = Span::createFromContext($context, $endpoint);
        $this->assertInstanceOf(Span::class, $span);
    }

    public function testStartSpanSuccess()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $endpoint = Endpoint::createAsEmpty();
        $span = Span::createFromContext($context, $endpoint);
        $timestamp = now();
        $span->start($timestamp);
        $this->assertEquals($timestamp, $span->getTimestamp());
    }
}
