<?php

namespace ZipkinTests\Unit;

use PHPUnit_Framework_TestCase;
use Zipkin\DefaultTracing;
use Zipkin\Endpoint;
use Zipkin\Propagation\CurrentTraceContext;
use Zipkin\Propagation\Propagation;
use Zipkin\Reporters\Noop;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Tracer;
use Zipkin\Tracing;

final class DefaultTracingTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultTracingCreationSuccess()
    {
        $localEndpoint = Endpoint::createAsEmpty();
        $reporter = new Noop();
        $sampler = BinarySampler::createAsNeverSample();
        $isNoop = $this->randomBool();

        $tracing = new DefaultTracing(
            $localEndpoint,
            $reporter,
            $sampler,
            false,
            CurrentTraceContext::create(),
            $isNoop
        );

        $this->assertInstanceOf(Tracing::class, $tracing);
        $this->assertInstanceOf(Tracer::class, $tracing->getTracer());
        $this->assertInstanceOf(Propagation::class, $tracing->getPropagation());
    }

    private function randomBool()
    {
        return (mt_rand(0, 1) === 1);
    }
}
