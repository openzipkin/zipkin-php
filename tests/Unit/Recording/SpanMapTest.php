<?php

namespace ZipkinTests\Unit\Recording;

use PHPUnit_Framework_TestCase;
use Zipkin\Endpoint;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Recording\Span;
use Zipkin\Recording\SpanMap;
use Zipkin\TraceContext;

/**
 * @covers SpanMap
 */
final class SpanMapTest extends PHPUnit_Framework_TestCase
{
    public function testCreateASpanMapSuccess()
    {
        $spanMap = SpanMap::create();
        $this->assertInstanceOf(SpanMap::class, $spanMap);
    }

    public function testGetReturnsOrCreateOnNonExistingSpan()
    {
        $spanMap = SpanMap::create();
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $endpoint = Endpoint::createAsEmpty();
        $span = $spanMap->getOrCreate($context, $endpoint);
        $this->assertInstanceOf(Span::class, $span);
    }

    public function testGetReturnsNullOnNonExistingSpan()
    {
        $spanMap = SpanMap::create();
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $this->assertNull($spanMap->get($context));
    }
}
