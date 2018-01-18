<?php

namespace ZipkinTests\Unit;

use PHPUnit_Framework_TestCase;
use Zipkin\Endpoint;
use Zipkin\Propagation\CurrentTraceContext;
use Zipkin\Reporters\Noop;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Tracing;
use Zipkin\TracingBuilder;

final class TracingBuilderTest extends PHPUnit_Framework_TestCase
{
    const SERVICE_NAME = 'service_name';

    public function testCreatingTracingWithDefaultValuesSuccess()
    {
        $tracing = TracingBuilder::create()->build();
        $this->assertInstanceOf(Tracing::class, $tracing);
        $this->assertEquals(false, $tracing->isNoop());
    }

    /**
     * @dataProvider boolProvider
     */
    public function testCreatingTracingIncludesExpectedValues($isNoop)
    {
        $endpoint = Endpoint::createAsEmpty();
        $reporter = new Noop();
        $sampler = BinarySampler::createAsAlwaysSample();
        $usesTraceId128bits = $this->randomBool();
        $currentTraceContext = CurrentTraceContext::create();

        $tracing = TracingBuilder::create()
            ->havingLocalServiceName(self::SERVICE_NAME)
            ->havingLocalEndpoint($endpoint)
            ->havingReporter($reporter)
            ->havingSampler($sampler)
            ->havingTraceId128bits($usesTraceId128bits)
            ->havingCurrentTraceContext($currentTraceContext)
            ->beingNoop($isNoop)
            ->build();

        $this->assertInstanceOf(Tracing::class, $tracing);
        $this->assertEquals($isNoop, $tracing->isNoop());
    }

    public function boolProvider()
    {
        return [
            [true],
            [false]
        ];
    }

    private function randomBool()
    {
        return (bool) mt_rand(0, 1);
    }
}
