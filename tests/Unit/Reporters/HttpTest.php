<?php

namespace ZipkinTests\Unit\Reporters;

use Zipkin\Endpoint;
use RuntimeException;
use Prophecy\Argument;
use Zipkin\Recording\Span;
use Zipkin\Reporters\Http;
use Psr\Log\LoggerInterface;
use Zipkin\Reporters\Metrics;
use PHPUnit\Framework\TestCase;
use Zipkin\Propagation\TraceContext;

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
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error()->shouldNotBeCalled();

        $mockFactory = HttpMockFactory::createAsSuccess();
        $httpReporter = new Http($mockFactory, [], $logger->reveal());
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
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error(HttpMockFactory::ERROR_MESSAGE)->shouldBeCalled();

        $mockFactory = HttpMockFactory::createAsFailing();
        $httpReporter = new Http($mockFactory, [], $logger->reveal());
        $httpReporter->report([$span]);
    }
}
