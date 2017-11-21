<?php

namespace ZipkingTests\Unit;

use PHPUnit_Framework_TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Zipkin\Endpoint;
use Zipkin\NoopSpan;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\RealSpan;
use Zipkin\Reporter;
use Zipkin\Sampler;
use Zipkin\Samplers\BinarySampler;
use Zipkin\TraceContext;
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

    public function setUp()
    {
        $this->reporter = $this->prophesize(Reporter::class);
        $this->sampler = $this->prophesize(Sampler::class);
    }

    public function testNewTraceSuccess()
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            $this->sampler->reveal(),
            false,
            false
        );

        $samplingFlags = DefaultSamplingFlags::create(true, false);

        $span = $tracer->newTrace($samplingFlags);

        $this->assertEquals(true, $span->getContext()->isSampled());
        $this->assertEquals(false, $span->getContext()->isDebug());
        $this->assertEquals(false, $span->getContext()->isTraceId128bits());
        $this->assertNull($span->getContext()->getParentId());
        $this->assertEquals($span->getContext()->getTraceId(), $span->getContext()->getSpanId());
    }

    public function testNewTraceSuccessWith128bits()
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            $this->sampler->reveal(),
            true,
            false
        );

        $samplingFlags = DefaultSamplingFlags::create(true, false);

        $span = $tracer->newTrace($samplingFlags);

        $this->assertEquals(true, $span->getContext()->isSampled());
        $this->assertEquals(false, $span->getContext()->isDebug());
        $this->assertEquals(true, $span->getContext()->isTraceId128bits());
        $this->assertNull($span->getContext()->getParentId());
        $this->assertEquals(strlen($span->getContext()->getTraceId()), 32);
    }

    public function testNewChildIsBeingCreatedAsNoop()
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            $this->sampler->reveal(),
            false,
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
            false
        );

        $span = $tracer->newTrace(DefaultSamplingFlags::createAsEmpty());
        $this->assertFalse($span->getContext()->isSampled());
    }
}
