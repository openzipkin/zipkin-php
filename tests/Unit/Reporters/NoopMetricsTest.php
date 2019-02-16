<?php

namespace ZipkinTests\Unit\Reporters;

use PHPUnit\Framework\TestCase;
use Zipkin\Reporters\Metrics;
use Zipkin\Reporters\NoopMetrics;

final class NoopMetricsTest extends TestCase
{
    public function testCreateNoopMetricsSuccess()
    {
        $noopMetrics = new NoopMetrics();
        $this->assertInstanceOf(Metrics::class, $noopMetrics);
    }
}
