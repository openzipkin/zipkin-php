<?php

namespace ZipkinTests\Unit;

use PHPUnit_Framework_TestCase;
use Zipkin\Endpoint;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\RealSpan;
use Zipkin\Recorder;
use Zipkin\Reporter;
use Zipkin\TraceContext;

class RealSpanTest extends PHPUnit_Framework_TestCase
{
    const TEST_NAME = 'test_span';
    const TEST_KIND = 'ab';
    const TEST_START_TIMESTAMP = 1472470996199000;

    public function testCreateRealSpanSuccess()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $recorder = $this->prophesize(Recorder::class);
        $span = RealSpan::create($context, $recorder);
        $this->assertEquals($context, $span->getContext());
    }

    public function testSetNameSuccess()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $recorder = $this->prophesize(Recorder::class);
        $recorder->setName($context, self::TEST_NAME)->shouldBeCalled();
        $span = RealSpan::create($context, $recorder->reveal());
        $span->setName(self::TEST_NAME);
    }

    public function testSetKindSuccess()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $recorder = $this->prophesize(Recorder::class);
        $recorder->setKind($context, self::TEST_KIND)->shouldBeCalled();
        $span = RealSpan::create($context, $recorder->reveal());
        $span->setKind(self::TEST_KIND);
    }
}
