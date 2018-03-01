<?php

namespace ZipkinTests\Unit\Propagation;

use PHPUnit_Framework_TestCase;
use Zipkin\Propagation\Exceptions\InvalidTraceContextArgument;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Propagation\TraceContext;

final class TraceContextTest extends PHPUnit_Framework_TestCase
{
    const EMPTY_SAMPLED = null;
    const EMPTY_DEBUG = false;

    const TEST_TRACE_ID = 'bd7a977555f6b982';
    const TEST_PARENT_ID = 'bd7a977555f6b983';
    const TEST_SPAN_ID = 'be2d01e33cc78d97';

    const TEST_INVALID_TRACE_ID = 'invalid_bd7a977555f6b982';
    const TEST_INVALID_PARENT_ID = 'invalid_bd7a977555f6b982';
    const TEST_INVALID_SPAN_ID = 'invalid_be2d01e33cc78d97';

    const IS_FINAL_MUTATION = true;

    private $hasAtLeastOneMutation;

    protected function setUp()
    {
        /* Makes sure there is at least one mutation */
        $this->hasAtLeastOneMutation = false;
    }

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
    public function testChangeSampledSuccess($sampled, $debug)
    {
        $samplingFlags = DefaultSamplingFlags::create($sampled, $debug);
        $traceContext = TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_SPAN_ID,
            self::TEST_PARENT_ID,
            $samplingFlags->isSampled(),
            $samplingFlags->isDebug()
        );

        $newSampled = !$sampled;

        $newTraceContext = $traceContext->withSampled($newSampled);

        $this->assertEquals($traceContext->getSpanId(), $newTraceContext->getSpanId());
        $this->assertEquals($traceContext->getTraceId(), $newTraceContext->getTraceId());
        $this->assertEquals($traceContext->getParentId(), $newTraceContext->getParentId());
        $this->assertEquals($newSampled, $newTraceContext->isSampled());
        $this->assertEquals($traceContext->isDebug(), $newTraceContext->isDebug());
    }

    /**
     * @dataProvider boolProvider
     */
    public function testCreateFailsDueToInvalidId($sampled, $debug)
    {
        $this->expectException(InvalidTraceContextArgument::class);
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
        $this->expectException(InvalidTraceContextArgument::class);
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
        $this->expectException(InvalidTraceContextArgument::class);
        $this->expectExceptionMessage('Invalid parent span id, got invalid_bd7a977555f6b982');

        TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_SPAN_ID,
            self::TEST_INVALID_PARENT_ID,
            $sampled,
            $debug
        );
    }

    /**
     * @dataProvider boolProvider
     */
    public function testIsEqualSuccessOnEqualContexts($sampled, $debug)
    {
        $traceContext1 = TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_SPAN_ID,
            self::TEST_PARENT_ID,
            $sampled,
            $debug
        );

        $traceContext2 = TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_SPAN_ID,
            self::TEST_PARENT_ID,
            $sampled,
            $debug
        );

        $this->assertTrue($traceContext1->isEqual($traceContext2));
    }

    /**
     * @dataProvider boolProvider
     */
    public function testIsEqualSuccessOnDifferentContexts($sampled, $debug)
    {
        $traceContext1 = TraceContext::create(
            $this->maybeMutate(self::TEST_TRACE_ID),
            $this->maybeMutate(self::TEST_SPAN_ID),
            $this->maybeMutate(self::TEST_PARENT_ID),
            $this->maybeMutate($sampled),
            $this->maybeMutate($debug, self::IS_FINAL_MUTATION)
        );

        $traceContext2 = TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_SPAN_ID,
            self::TEST_PARENT_ID,
            $sampled,
            $debug
        );

        $this->assertFalse($traceContext1->isEqual($traceContext2));
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

    private function maybeMutate($value, $isFinalMutation = false)
    {
        if ($isFinalMutation && !$this->hasAtLeastOneMutation) {
            $shouldMutate = true;
        } else {
            $shouldMutate = (bool) mt_rand(0, 1);
        }

        if ($shouldMutate === false) {
            return $value;
        }

        $this->hasAtLeastOneMutation = true;

        if ($value === (string) $value) {
            $value = substr($value, 0, -1) . mt_rand(0, 9);
        }

        if ($value === (bool) $value) {
            $value = !$value;
        }

        return $value;
    }
}
