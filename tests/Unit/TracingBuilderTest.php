<?php

namespace ZipkinTests\Unit;

use PHPUnit_Framework_TestCase;
use Zipkin\Tracing;
use Zipkin\TracingBuilder;

final class TracingBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testCreatingTracingWithDefaultValuesSuccess()
    {
        $tracing = TracingBuilder::create()->build();
        $this->assertInstanceOf(Tracing::class, $tracing);
        $this->assertEquals(false, $tracing->isNoop());
    }
}
