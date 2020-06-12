<?php

namespace ZipkinTests\Unit;

use Zipkin\Tracing;
use Zipkin\Endpoint;
use Zipkin\Reporters\Noop;
use Zipkin\Reporters\InMemory;
use Zipkin\TracingBuilder;
use Zipkin\Propagation\Getter;
use Zipkin\Propagation\Setter;
use PHPUnit\Framework\TestCase;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Propagation\Propagation;
use Zipkin\Propagation\CurrentTraceContext;

final class TracingBuilderTest extends TestCase
{
    private const SERVICE_NAME = 'service_name';

    public function testCreatingTracingWithDefaultValuesSuccess()
    {
        $tracing = TracingBuilder::create()->build();
        $this->assertInstanceOf(Tracing::class, $tracing);
        $this->assertEquals(false, $tracing->isNoop());
        $this->assertInstanceOf(Propagation::class, $tracing->getPropagation());
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
        $currentTraceContext = new CurrentTraceContext;
        $propagation = new class() implements Propagation {
            public function getKeys(): array
            {
                return [];
            }
            public function getInjector(Setter $setter): callable
            {
                return function () {
                };
            }
            public function getExtractor(Getter $getter): callable
            {
                return function () {
                };
            }
            public function supportsJoin(): bool
            {
                return true;
            }
        };

        $tracing = TracingBuilder::create()
            ->havingLocalServiceName(self::SERVICE_NAME)
            ->havingLocalEndpoint($endpoint)
            ->havingReporter($reporter)
            ->havingSampler($sampler)
            ->havingTraceId128bits($usesTraceId128bits)
            ->havingCurrentTraceContext($currentTraceContext)
            ->beingNoop($isNoop)
            ->supportingJoin(true)
            ->havingPropagation($propagation)
            ->build();

        $this->assertInstanceOf(Tracing::class, $tracing);
        $this->assertEquals($isNoop, $tracing->isNoop());
        $this->assertSame($propagation, $tracing->getPropagation());
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

    public function testAlwaysEmitSpans()
    {
        // If `alwaysEmittingSpans(true)` is called, we should be emitting the
        // spans even if the trace isn't sampled
        $endpoint = Endpoint::createAsEmpty();
        $reporter = new InMemory();
        $sampler = BinarySampler::createAsNeverSample();

        $tracing = TracingBuilder::create()
            ->havingLocalServiceName(self::SERVICE_NAME)
            ->havingLocalEndpoint($endpoint)
            ->havingReporter($reporter)
            ->havingSampler($sampler)
            ->alwaysEmittingSpans(true)
            ->build();
        $tracer = $tracing->getTracer();

        $span = $tracer->newTrace();
        $span->setName('test');
        $span->start();
        $span->finish();

        $tracer->flush();
        $spans = $reporter->flush();
        $this->assertCount(1, $spans);
        $this->assertFalse($spans[0]->isSampled());
    }

    public function testDontEmitByDefault()
    {
        // By default, let's verify that we don't emit any span if the
        // trace isn't sampled.
        $endpoint = Endpoint::createAsEmpty();
        $reporter = new InMemory();
        $sampler = BinarySampler::createAsNeverSample();

        $tracing = TracingBuilder::create()
            ->havingLocalServiceName(self::SERVICE_NAME)
            ->havingLocalEndpoint($endpoint)
            ->havingReporter($reporter)
            ->havingSampler($sampler)
            ->build();
        $tracer = $tracing->getTracer();

        $span = $tracer->newTrace();
        $span->setName('test');
        $span->start();
        $span->finish();

        $tracer->flush();
        $spans = $reporter->flush();
        $this->assertCount(0, $spans);
    }
}
