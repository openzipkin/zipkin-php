<?php

namespace ZipkinTests\Unit\Reporters;

use PHPUnit\Framework\TestCase;
use Zipkin\Reporter;
use Zipkin\Reporters\Noop;

final class NoopTest extends TestCase
{
    public function testCreateNoopReporterSuccess()
    {
        $noopReporter = new Noop();
        $this->assertInstanceOf(Reporter::class, $noopReporter);
    }
}
