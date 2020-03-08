<?php

namespace ZipkinTests\Unit;

use PHPUnit\Framework\TestCase;
use Zipkin\NoopSpan;
use Zipkin\Propagation\TraceContext;

final class NoopSpanTest extends TestCase
{
    public function testCreateNoopSpanSuccess()
    {
        $context = TraceContext::createAsRoot();
        $span = new NoopSpan($context);
        $this->assertTrue($span instanceof NoopSpan);
    }
}
