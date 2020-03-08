<?php

namespace ZipkinTests\Unit\Recording;

use PHPUnit\Framework\TestCase;
use Zipkin\Endpoint;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Recording\Span;
use Zipkin\Recording\SpanMap;
use Zipkin\Propagation\TraceContext;

final class SpanMapTest extends TestCase
{
    public function testCreateASpanMapSuccess()
    {
        $spanMap = new SpanMap;
        $this->assertInstanceOf(SpanMap::class, $spanMap);
    }

    public function testGetReturnsOrCreateOnNonExistingSpan()
    {
        $spanMap = new SpanMap;
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $endpoint = Endpoint::createAsEmpty();
        $span = $spanMap->getOrCreate($context, $endpoint);
        $this->assertInstanceOf(Span::class, $span);
    }

    public function testGetReturnsNullOnNonExistingSpan()
    {
        $spanMap = new SpanMap;
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $this->assertNull($spanMap->get($context));
    }

    public function testGetReturnsDifferentObjects()
    {
        $spanMap = new SpanMap;
        $endpoint = Endpoint::createAsEmpty();
        $rootSpan = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $recordedSpans = [];
        for ($i = 0; $i < 5; $i++) {
            $context = TraceContext::createFromParent($rootSpan);
            $recordedSpans[$i] = $spanMap->getOrCreate($context, $endpoint);
        }
        for ($i = 0; $i < count($recordedSpans); $i++) {
            for ($j = $i + 1; $j < count($recordedSpans); $j++) {
                $this->assertNotSame($recordedSpans[$i], $recordedSpans[$j]);
            }
        }
    }

    public function testRemoveReturnsEmptyAfterRemoval()
    {
        $spanMap = new SpanMap;
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $endpoint = Endpoint::createAsEmpty();
        $spanMap->getOrCreate($context, $endpoint);
        $spanMap->remove($context);
        $this->assertNull($spanMap->get($context));
    }

    public function testRemoveAllReturnsEmptyAfterRemoval()
    {
        $spanMap = new SpanMap;
        $contexts = [];
        $numberOfContexts = 3;

        for ($i = 0; $i < $numberOfContexts; $i++) {
            $contexts[$i] = TraceContext::createAsRoot();
            $endpoint = Endpoint::createAsEmpty();
            $spanMap->getOrCreate($contexts[$i], $endpoint);
        }

        $spanMap->removeAll();

        for ($i = 0; $i < $numberOfContexts; $i++) {
            $this->assertNull($spanMap->get($contexts[$i]));
        }
    }
}
