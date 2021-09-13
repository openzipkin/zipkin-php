<?php

namespace ZipkinTests\Unit\Reporters;

use Zipkin\Reporters\Log;
use Zipkin\Reporter;
use Zipkin\Recording\Span;
use Zipkin\Propagation\TraceContext;
use Zipkin\Endpoint;
use Psr\Log\LoggerInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use PHPUnit\Framework\TestCase;

final class LogTest extends TestCase
{
    use ProphecyTrait;

    public function testReportSpans()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->info(Argument::that(function ($serialized) {
            return strlen($serialized) > 2;
        }))->shouldBeCalled();

        $span = Span::createFromContext(TraceContext::createAsRoot(), Endpoint::createAsEmpty());

        $reporter = new Log($logger->reveal());
        $reporter->report([$span]);
    }
}
