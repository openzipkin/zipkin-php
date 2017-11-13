<?php

namespace ZipkingTests\Unit;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\TraceContext;

final class TraceContextTest extends PHPUnit_Framework_TestCase
{
    const TEST_TRACE_ID = 'bd7a977555f6b982';
    const TEST_PARENT_ID = 'bd7a977555f6b982';
    const TEST_SPAN_ID = 'be2d01e33cc78d97';

    const TEST_INVALID_TRACE_ID = 'invalid_bd7a977555f6b982';
    const TEST_INVALID_PARENT_ID = 'invalid_bd7a977555f6b982';
    const TEST_INVALID_SPAN_ID = 'invalid_be2d01e33cc78d97';

    public function testCreateAsRootSuccess()
    {
        $sampled = $this->randomBool();
        $debug = $this->randomBool();
        $samplingFlags = DefaultSamplingFlags::create($sampled, $debug);
        $context = TraceContext::createAsRoot($samplingFlags);

        $this->assertNotNull($context->getTraceId());
        $this->assertNotNull($context->getSpanId());
        $this->assertEquals(null, $context->getParentId());
        $this->assertEquals($sampled, $context->isSampled());
        $this->assertEquals($debug, $context->isDebug());
        $this->assertEquals([], $context->getExtra());
    }

    public function testCreateFromParentSuccess()
    {
        $sampled = $this->randomBool();
        $debug = $this->randomBool();
        $extra = [
            'field_1' => 'value_1'
        ];
        $samplingFlags = DefaultSamplingFlags::create($sampled, $debug);
        $parentContext = TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_SPAN_ID,
            self::TEST_PARENT_ID,
            $samplingFlags->isSampled(),
            $samplingFlags->isDebug(),
            $extra
        );

        $childContext = TraceContext::createFromParent($parentContext);

        $this->assertNotNull($childContext->getSpanId());
        $this->assertEquals(self::TEST_TRACE_ID, $childContext->getTraceId());
        $this->assertEquals(self::TEST_SPAN_ID, $childContext->getParentId());
        $this->assertEquals($sampled, $childContext->isSampled());
        $this->assertEquals($debug, $childContext->isDebug());
        $this->assertEquals($extra, $childContext->getExtra());
    }

    public function testCreateFailsDueToInvalidId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid trace id, got invalid_bd7a977555f6b982');

        $sampled = $this->randomBool();
        $debug = $this->randomBool();
        TraceContext::create(
            self::TEST_INVALID_TRACE_ID,
            self::TEST_SPAN_ID,
            null,
            $sampled,
            $debug
        );
    }

    public function testCreateFailsDueToInvalidSpanId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid span id, got invalid_be2d01e33cc78d97');

        $sampled = $this->randomBool();
        $debug = $this->randomBool();
        TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_INVALID_SPAN_ID,
            null,
            $sampled,
            $debug
        );
    }

    public function testCreateFailsDueToInvalidParentSpanId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid parent span id, got invalid_bd7a977555f6b982');

        $sampled = $this->randomBool();
        $debug = $this->randomBool();
        TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_SPAN_ID,
            self::TEST_INVALID_PARENT_ID,
            $sampled,
            $debug
        );
    }

    private function randomBool()
    {
        return (mt_rand(0, 1) === 1);
    }
}
