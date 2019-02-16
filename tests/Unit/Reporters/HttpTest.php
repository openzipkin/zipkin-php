<?php

namespace ZipkinTests\Unit\Reporters;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use RuntimeException;
use Zipkin\Endpoint;
use Zipkin\Propagation\TraceContext;
use Zipkin\Recording\Span;
use Zipkin\Reporters\Http;
use Zipkin\Reporters\Metrics;

final class HttpTest extends TestCase
{
    const PAYLOAD = '[{"id":"%s","name":null,"traceId":"%s","parentId":null,'
        . '"timestamp":null,"duration":null,"debug":false,"localEndpoint":{"serviceName":""}}]';

    public function testCreateHttpReporterWithDefaultDependencies()
    {
        $httpReporter = new Http();
        $this->assertInstanceOf(Http::class, $httpReporter);
    }

    public function testHttpReporterSuccess()
    {
        $context = TraceContext::createAsRoot();
        $localEndpoint = Endpoint::createAsEmpty();
        $span = Span::createFromContext($context, $localEndpoint);
        $payload = sprintf(self::PAYLOAD, $context->getSpanId(), $context->getTraceId());
        $metrics = $this->prophesize(Metrics::class);
        $metrics->incrementSpans(1)->shouldBeCalled();
        $metrics->incrementMessages()->shouldBeCalled();
        $metrics->incrementSpanBytes(strlen($payload))->shouldBeCalled();
        $metrics->incrementMessageBytes(strlen($payload))->shouldBeCalled();

        $mockFactory = HttpMockFactory::createAsSuccess();
        $httpReporter = new Http($mockFactory, [], $metrics->reveal());
        $httpReporter->report([$span]);

        $this->assertEquals(
            $payload,
            $mockFactory->retrieveContent()
        );
    }

    public function testHttpReporterFails()
    {
        $context = TraceContext::createAsRoot();
        $localEndpoint = Endpoint::createAsEmpty();
        $span = Span::createFromContext($context, $localEndpoint);
        $payload = sprintf(self::PAYLOAD, $context->getSpanId(), $context->getTraceId());
        $metrics = $this->prophesize(Metrics::class);
        $metrics->incrementSpans(1)->shouldBeCalled();
        $metrics->incrementMessages()->shouldBeCalled();
        $metrics->incrementSpanBytes(strlen($payload))->shouldBeCalled();
        $metrics->incrementMessageBytes(strlen($payload))->shouldBeCalled();
        $metrics->incrementSpansDropped(1)->shouldBeCalled();
        $metrics->incrementMessagesDropped(Argument::type(RuntimeException::class))->shouldBeCalled();

        $mockFactory = HttpMockFactory::createAsFailing();
        $httpReporter = new Http($mockFactory, [], $metrics->reveal());
        $httpReporter->report([$span]);
    }
}
