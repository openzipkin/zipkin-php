<?php

namespace ZipkinTests\Unit;

use PHPUnit_Framework_TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Zipkin\Endpoint;
use Zipkin\NoopSpan;
use Zipkin\Propagation\CurrentTraceContext;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\RealSpan;
use Zipkin\Reporter;
use Zipkin\Sampler;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Propagation\TraceContext;
use Zipkin\Tracer;

final class TracerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectProphecy|Reporter
     */
    private $reporter;

    /**
     * @var ObjectProphecy|Sampler
     */
    private $sampler;

    /**
     * @var CurrentTraceContext
     */
    private $currentTracerContext;

    public function setUp()
    {
        $this->reporter = $this->prophesize(Reporter::class);
        $this->sampler = $this->prophesize(Sampler::class);
        $this->currentTracerContext = CurrentTraceContext::create();
    }

    public function testNewTraceSuccess()
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            $this->sampler->reveal(),
            false,
            $this->currentTracerContext,
            false
        );

        $samplingFlags = DefaultSamplingFlags::create(true, false);

        $span = $tracer->newTrace($samplingFlags);

        $this->assertEquals(true, $span->getContext()->isSampled());
        $this->assertEquals(false, $span->getContext()->isDebug());
        $this->assertNull($span->getContext()->getParentId());
        $this->assertFalse($span->getContext()->usesTraceId128bits());
        $this->assertEquals($span->getContext()->getTraceId(), $span->getContext()->getSpanId());
    }

    public function testNewTraceSuccessWith128bits()
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            $this->sampler->reveal(),
            true,
            CurrentTraceContext::create(),
            false
        );

        $samplingFlags = DefaultSamplingFlags::create(true, false);

        $span = $tracer->newTrace($samplingFlags);

        $this->assertEquals(true, $span->getContext()->isSampled());
        $this->assertEquals(false, $span->getContext()->isDebug());
        $this->assertNull($span->getContext()->getParentId());
        $this->assertTrue($span->getContext()->usesTraceId128bits());
    }

    public function testNewChildIsBeingCreatedAsNoop()
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            $this->sampler->reveal(),
            false,
            $this->currentTracerContext,
            false
        );

        $samplingFlags = DefaultSamplingFlags::create(false, false);

        $traceContext = TraceContext::createAsRoot($samplingFlags);

        $span = $tracer->newChild($traceContext);

        $this->assertInstanceOf(NoopSpan::class, $span);
    }

    public function testNewChildIsBeingCreatedAsSampled()
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            $this->sampler->reveal(),
            false,
            $this->currentTracerContext,
            false
        );

        $samplingFlags = DefaultSamplingFlags::create(true, false);

        $traceContext = TraceContext::createAsRoot($samplingFlags);

        $span = $tracer->newChild($traceContext);

        $this->assertInstanceOf(RealSpan::class, $span);
    }

    public function testNewTraceIsSampledOnAlwaysSampling()
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            BinarySampler::createAsAlwaysSample(),
            false,
            $this->currentTracerContext,
            false
        );

        $span = $tracer->newTrace(DefaultSamplingFlags::createAsEmpty());
        $this->assertTrue($span->getContext()->isSampled());
    }

    public function testNewTraceIsSampledOnNeverSampling()
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            BinarySampler::createAsNeverSample(),
            false,
            $this->currentTracerContext,
            false
        );

        $span = $tracer->newTrace(DefaultSamplingFlags::createAsEmpty());
        $this->assertFalse($span->getContext()->isSampled());
    }

    public function testNextSpanIsCreatedFromCurrentTraceContext()
    {
        $context = TraceContext::createAsRoot();

        $this->currentTracerContext->createScopeAndRetrieveItsCloser($context);

        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            BinarySampler::createAsNeverSample(),
            false,
            $this->currentTracerContext,
            false
        );

        $span = $tracer->nextSpan();

        $this->assertContextParentOf($context, $span->getContext());
    }

    public function testNextSpanIsCreatedFromContext()
    {
        $context = TraceContext::createAsRoot();

        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            BinarySampler::createAsNeverSample(),
            false,
            $this->currentTracerContext,
            false
        );

        $span = $tracer->nextSpan($context);
        $this->assertContextParentOf($context, $span->getContext());
    }

    /**
     * @dataProvider samplingFlagsDataProvider
     */
    public function testNextSpanIsCreatedFromSamplingFlags($sampled, $debug)
    {
        $samplingFlags = DefaultSamplingFlags::create($sampled, $debug);

        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            BinarySampler::createAsNeverSample(),
            false,
            $this->currentTracerContext,
            false
        );

        $span = $tracer->nextSpan($samplingFlags);

        $this->assertSameSamplingFlags($samplingFlags, $span->getContext());
    }

    /**
     * @dataProvider emptySamplingFlagsDataProvider
     */
    public function testNextSpanIsCreatedFromEmptySamplingFlags($sampler, $isNoop)
    {
        $samplingFlags = DefaultSamplingFlags::createAsEmpty();

        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            $sampler,
            false,
            $this->currentTracerContext,
            false
        );

        $span = $tracer->nextSpan($samplingFlags);
        $this->assertEquals($isNoop, $span->isNoop());
    }

    public function emptySamplingFlagsDataProvider()
    {
        return [
            [BinarySampler::createAsNeverSample(), true],
            [BinarySampler::createAsAlwaysSample(), false],
        ];
    }

    public function samplingFlagsDataProvider()
    {
        return [
            [ null, true ],
            [ null, false ],
            [ true, true ],
            [ true, false ],
            [ false, true ],
            [ false, false ],
        ];
    }

    private function assertSameSamplingFlags(SamplingFlags $samplingFlags1, SamplingFlags $samplingFlags2)
    {
        $this->assertEquals($samplingFlags1->isSampled(), $samplingFlags2->isSampled());
        $this->assertEquals($samplingFlags1->isDebug(), $samplingFlags2->isDebug());
    }

    private function assertContextParentOf(TraceContext $parentContext, TraceContext $childContext)
    {
        $this->assertEquals($parentContext->getTraceId(), $childContext->getTraceId());
        $this->assertEquals($parentContext->getSpanId(), $childContext->getParentId());
        $this->assertEquals($parentContext->isDebug(), $childContext->isDebug());
        $this->assertEquals($parentContext->isSampled(), $childContext->isSampled());
    }

    public function testSetSpanInScope()
    {
        $context = TraceContext::createAsRoot();
        $this->currentTracerContext->createScopeAndRetrieveItsCloser($context);

        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            BinarySampler::createAsNeverSample(),
            false,
            $this->currentTracerContext,
            false
        );

        $currentSpan = $tracer->getCurrentSpan();

        $this->assertEquals($context, $currentSpan->getContext());
    }

    /**
     * @dataProvider spanForScopeProvider
     */
    public function testOpenScopeReturnsScopeCloser($spanForScope)
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            BinarySampler::createAsNeverSample(),
            false,
            CurrentTraceContext::create(),
            false
        );

        $scopeCloser = $tracer->openScope($spanForScope);

        $this->assertTrue(is_callable($scopeCloser));
    }

    public function spanForScopeProvider()
    {
        return [
            [null],
            [NoopSpan::create(TraceContext::createAsRoot())]
        ];
    }
}
