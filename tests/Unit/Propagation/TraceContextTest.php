<?php

namespace ZipkinTests\Unit\Propagation;

use Zipkin\Propagation\TraceContext;
use Zipkin\Propagation\Exceptions\InvalidTraceContextArgument;
use Zipkin\Propagation\DefaultSamplingFlags;
use PHPUnit\Framework\TestCase;

final class TraceContextTest extends TestCase
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

    protected function setUp(): void
    {
        /* Makes sure there is at least one mutation */
        $this->hasAtLeastOneMutation = false;
    }

    /**
     * @dataProvider sampledDebugProvider
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
     * @dataProvider sampledDebugProvider
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
     * @dataProvider sampledDebugProvider
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
     * @dataProvider sampledDebugProvider
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
        $this->assertFalse($newTraceContext->isDebug());
    }

    /**
     * @dataProvider sampledDebugProvider
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
     * @dataProvider sampledDebugProvider
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
     * @dataProvider sampledDebugProvider
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
     * @dataProvider sampledDebugProvider
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
     * @dataProvider sampledDebugProvider
     */
    public function testIsEqualSuccessOnDifferentContexts($sampled, $debug)
    {
        $traceContext1 = TraceContext::create(
            $this->maybeMutate(self::TEST_TRACE_ID),
            $this->maybeMutate(self::TEST_SPAN_ID),
            $this->maybeMutate(self::TEST_PARENT_ID, self::IS_FINAL_MUTATION),
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

        $this->assertFalse($traceContext1->isEqual($traceContext2));
    }

    public function testIsSharedSuccess()
    {
        $traceContext = TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_SPAN_ID,
            self::TEST_PARENT_ID,
            null,
            false,
            true
        );

        $this->assertTrue($traceContext->isShared());

        $traceContext = TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_SPAN_ID,
            self::TEST_PARENT_ID,
            null,
            false,
            false
        );

        $this->assertFalse($traceContext->isShared());
    }

    public function testWithSharedSuccess()
    {
        $traceContext = TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_SPAN_ID,
            self::TEST_PARENT_ID,
            null,
            false,
            false
        );

        $sharedTraceContext = $traceContext->withShared(true);

        $this->assertTrue($sharedTraceContext->isEqual($traceContext));
    }

    public function sampledDebugProvider()
    {
        return [
            [true, true],
            [true, false],
            // [false, true] is a non sense combination, hence ignored.
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
            $value = dechex(hexdec($value) + 1);
        }

        if ($value === (bool) $value) {
            $value = !$value;
        }

        return $value;
    }
}
