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
            false
        );

        $samplingFlags = DefaultSamplingFlags::create(true, false);

        $span = $tracer->newTrace($samplingFlags);

        $this->assertEquals(true, $span->getContext()->getSampled());
        $this->assertEquals(false, $span->getContext()->debug());
        $this->assertNull($span->getContext()->getParentId());
        $this->assertEquals($span->getContext()->getTraceId(), $span->getContext()->getSpanId());
    }

    public function testNewChildIsBeingCreatedAsNoop()
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            $this->sampler->reveal(),
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
            false
        );

        $samplingFlags = DefaultSamplingFlags::create(true, false);

        $traceContext = TraceContext::createAsRoot($samplingFlags);

        $span = $tracer->newChild($traceContext);

        $this->assertInstanceOf(RealSpan::class, $span);
    }
}