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
        $this->assertEquals($sampled, $context->getSampled());
        $this->assertEquals($debug, $context->debug());
    }

    public function testCreateFromParentSuccess()
    {
        $sampled = $this->randomBool();
        $debug = $this->randomBool();
        $samplingFlags = DefaultSamplingFlags::create($sampled, $debug);
        $parentContext = TraceContext::createAsRoot($samplingFlags);
        $parentContext->setTraceId(self::TEST_TRACE_ID);
        $parentContext->setParentId(self::TEST_PARENT_ID);
        $parentContext->setSpanId(self::TEST_SPAN_ID);

        $childContext = TraceContext::createFromParent($parentContext);

        $this->assertNotNull($childContext->getSpanId());
        $this->assertEquals(self::TEST_TRACE_ID, $childContext->getTraceId());
        $this->assertEquals(self::TEST_SPAN_ID, $childContext->getParentId());
        $this->assertEquals($sampled, $childContext->getSampled());
        $this->assertEquals($debug, $childContext->debug());
    }

    public function testSetSpanIdFailsDueToInvalidId()
    {
        $this->expectException(InvalidArgumentException::class);

        $sampled = $this->randomBool();
        $debug = $this->randomBool();
        $samplingFlags = DefaultSamplingFlags::create($sampled, $debug);
        $parentContext = TraceContext::createAsRoot($samplingFlags);
        $parentContext->setSpanId(self::TEST_INVALID_SPAN_ID);
    }

    public function testSetParentIdFailsDueToInvalidId()
    {
        $this->expectException(InvalidArgumentException::class);

        $sampled = $this->randomBool();
        $debug = $this->randomBool();
        $samplingFlags = DefaultSamplingFlags::create($sampled, $debug);
        $parentContext = TraceContext::createAsRoot($samplingFlags);
        $parentContext->setParentId(self::TEST_INVALID_PARENT_ID);
    }

    public function testSetTraceIdFailsDueToInvalidId()
    {
        $this->expectException(InvalidArgumentException::class);

        $sampled = $this->randomBool();
        $debug = $this->randomBool();
        $samplingFlags = DefaultSamplingFlags::create($sampled, $debug);
        $parentContext = TraceContext::createAsRoot($samplingFlags);
        $parentContext->setTraceId(self::TEST_INVALID_TRACE_ID);
    }

    private function randomBool()
    {
        return (mt_rand(0, 1) === 1);
    }
}
