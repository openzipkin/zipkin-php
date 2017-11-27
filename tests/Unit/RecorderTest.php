<?php

namespace ZipkinTests\Unit;

use PHPUnit_Framework_TestCase;
use Zipkin\Endpoint;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Recorder;
use Zipkin\Reporter;
use function Zipkin\Timestamp\now;
use Zipkin\Propagation\TraceContext;

final class RecorderTest extends PHPUnit_Framework_TestCase
{
    public function testGetTimestampReturnsNullWhenThereIsNoSuchTraceContext()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $reporter = $this->prophesize(Reporter::class);
        $recorder = new Recorder(Endpoint::createAsEmpty(), $reporter->reveal(), false);
        $this->assertNull($recorder->getTimestamp($context));
    }

    public function testStartSuccess()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $reporter = $this->prophesize(Reporter::class);
        $recorder = new Recorder(Endpoint::createAsEmpty(), $reporter->reveal(), false);
        $timestamp = now();
        $recorder->start($context, $timestamp);
        $this->assertEquals($timestamp, $recorder->getTimestamp($context));
    }
}
