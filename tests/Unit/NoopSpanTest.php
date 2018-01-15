<?php

namespace ZipkinTests\Unit;

use PHPUnit_Framework_TestCase;
use Zipkin\NoopSpan;
use Zipkin\Propagation\TraceContext;

final class NoopSpanTest extends PHPUnit_Framework_TestCase
{
    public function testCreateNoopSpanSuccess()
    {
        $context = TraceContext::createAsRoot();
        $span = NoopSpan::create($context);
        $this->assertTrue($span instanceof NoopSpan);
    }
}
