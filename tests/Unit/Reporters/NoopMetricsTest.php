<?php

namespace ZipkinTests\Unit\Reporters;

use PHPUnit_Framework_TestCase;
use Zipkin\Reporters\Metrics;
use Zipkin\Reporters\NoopMetrics;

final class NoopMetricsTest extends PHPUnit_Framework_TestCase
{
    public function testCreateNoopMetricsSuccess()
    {
        $noopMetrics = new NoopMetrics();
        $this->assertInstanceOf(Metrics::class, $noopMetrics);
    }
}
