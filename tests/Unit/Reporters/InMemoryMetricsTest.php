<?php

namespace ZipkinTests\Unit\Reporters;

use PHPUnit_Framework_TestCase;
use Zipkin\Reporters\InMemoryMetrics;

final class InMemoryMetricsTest extends PHPUnit_Framework_TestCase
{
    public function testIncrementSpansSuccess()
    {
        $metrics = new InMemoryMetrics();
        $metrics->incrementSpans(10);
        $this->assertEquals(10, $metrics->getSpans());
    }

    public function testIncrementSpansDroppedSuccess()
    {
        $metrics = new InMemoryMetrics();
        $metrics->incrementSpansDropped(10);
        $this->assertEquals(10, $metrics->getSpansDropped());
    }
}
