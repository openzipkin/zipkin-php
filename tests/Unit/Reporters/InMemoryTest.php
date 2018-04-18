<?php

namespace ZipkinTests\Unit\Reporters;

use PHPUnit_Framework_TestCase;
use Zipkin\Reporters\InMemory;
use Zipkin\Samplers\BinarySampler;
use Zipkin\TracingBuilder;

final class InMemoryTest extends PHPUnit_Framework_TestCase
{
    public function testReportOfSpans()
    {
        $reporter = new InMemory();
        $tracing = TracingBuilder::create()
            ->havingSampler(BinarySampler::createAsAlwaysSample())
            ->havingReporter($reporter)
            ->build();

        $span = $tracing->getTracer()->nextSpan();
        $span->start();
        $span->finish();

        $tracing->getTracer()->flush();
        $flushedSpans = $reporter->flush();

        $this->assertCount(1, $flushedSpans);
    }
}
