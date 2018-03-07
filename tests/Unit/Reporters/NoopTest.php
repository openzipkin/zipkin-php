<?php

namespace ZipkinTests\Unit\Reporters;

use PHPUnit_Framework_TestCase;
use Zipkin\Reporter;
use Zipkin\Reporters\Noop;

final class NoopTest extends PHPUnit_Framework_TestCase
{
    public function testCreateNoopReporterSuccess()
    {
        $noopReporter = new Noop();
        $this->assertInstanceOf(Reporter::class, $noopReporter);
    }
}
