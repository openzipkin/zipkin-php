<?php

namespace ZipkinTests\Unit\Propagation;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Propagation\TraceContext;

final class TraceContextTest extends PHPUnit_Framework_TestCase
{
    const TEST_TRACE_ID = 'bd7a977555f6b982';
    const TEST_PARENT_ID = 'bd7a977555f6b982';
    const TEST_SPAN_ID = 'be2d01e33cc78d97';

    const TEST_INVALID_TRACE_ID = 'invalid_bd7a977555f6b982';
    const TEST_INVALID_PARENT_ID = 'invalid_bd7a977555f6b982';
    const TEST_INVALID_SPAN_ID = 'invalid_be2d01e33cc78d97';

    /**
     * @dataProvider boolProvider
     */
    public function testCreateAsRootSuccess($sampled, $debug)
    {
        $samplingFlags = DefaultSamplingFlags::create($sampled, $debug);
        $context = TraceContext::createAsRoot($samplingFlags);

        $this->assertNotNull($context->getTraceId());
        $this->assertNotNull($context->getSpanId());
        $this->assertEquals(null, $context->getParentId());
        $this->assertEquals($sampled, $context->isSampled());
        $this->assertEquals($debug, $context->isDebug());
    }

    /**
     * @dataProvider boolProvider
     */
    public function testCreateAsRootSuccessWithTraceId128bits($sampled, $debug)
    {
        $samplingFlags = DefaultSamplingFlags::create($sampled, $debug);
        $context = TraceContext::createAsRoot($samplingFlags, true);

        $this->assertNotNull($context->getTraceId());
        $this->assertTrue($context->usesTraceId128bits());
        $this->assertNotNull($context->getSpanId());
        $this->assertEquals(null, $context->getParentId());
        $this->assertEquals($sampled, $context->isSampled());
        $this->assertEquals($debug, $context->isDebug());
        $this->assertEquals(32, strlen($context->getTraceId()));
    }

    /**
     * @dataProvider boolProvider
     */
    public function testCreateFromParentSuccess($sampled, $debug)
    {
        $samplingFlags = DefaultSamplingFlags::create($sampled, $debug);
        $parentContext = TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_SPAN_ID,
            self::TEST_PARENT_ID,
            $samplingFlags->isSampled(),
            $samplingFlags->isDebug()
        );

        $childContext = TraceContext::createFromParent($parentContext);

        $this->assertNotNull($childContext->getSpanId());
        $this->assertEquals(self::TEST_TRACE_ID, $childContext->getTraceId());
        $this->assertEquals(self::TEST_SPAN_ID, $childContext->getParentId());
        $this->assertEquals($sampled, $childContext->isSampled());
        $this->assertEquals($debug, $childContext->isDebug());
    }

    /**
     * @dataProvider boolProvider
     */
    public function testCreateFailsDueToInvalidId($sampled, $debug)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid trace id, got invalid_bd7a977555f6b982');

        TraceContext::create(
            self::TEST_INVALID_TRACE_ID,
            self::TEST_SPAN_ID,
            null,
            $sampled,
            $debug
        );
    }

    /**
     * @dataProvider boolProvider
     */
    public function testCreateFailsDueToInvalidSpanId($sampled, $debug)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid span id, got invalid_be2d01e33cc78d97');

        TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_INVALID_SPAN_ID,
            null,
            $sampled,
            $debug
        );
    }

    /**
     * @dataProvider boolProvider
     */
    public function testCreateFailsDueToInvalidParentSpanId($sampled, $debug)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid parent span id, got invalid_bd7a977555f6b982');

        TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_SPAN_ID,
            self::TEST_INVALID_PARENT_ID,
            $sampled,
            $debug
        );
    }

    public function boolProvider()
    {
        return [
            [true, true],
            [true, false],
            [false, true],
            [false, false],
        ];
    }
}
