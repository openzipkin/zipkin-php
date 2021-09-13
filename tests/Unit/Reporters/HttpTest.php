<?php

namespace ZipkinTests\Unit\Reporters;

use Zipkin\Timestamp;
use Zipkin\Reporters\Http\CurlFactory;
use Zipkin\Reporters\Http;
use Zipkin\Recording\Span;
use Zipkin\Propagation\TraceContext;
use Zipkin\Endpoint;
use TypeError;
use Psr\Log\LoggerInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use PHPUnit\Framework\TestCase;

final class HttpTest extends TestCase
{
    use ProphecyTrait;

    public const PAYLOAD = '[{"id":"%s","traceId":"%s",'
        . '"timestamp":%d,"name":"test","localEndpoint":{"serviceName":"unknown"}}]';

    public function testHttpReporterSuccess()
    {
        $context = TraceContext::createAsRoot();
        $localEndpoint = Endpoint::createAsEmpty();
        $span = Span::createFromContext($context, $localEndpoint);
        $now = Timestamp\now();
        $span->start($now);
        $span->setName('test');
        $payload = sprintf(self::PAYLOAD, $context->getSpanId(), $context->getTraceId(), $now);
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error()->shouldNotBeCalled();

        $mockFactory = HttpMockFactory::createAsSuccess();
        $httpReporter = new Http([], $mockFactory, $logger->reveal());
        $httpReporter->report([$span]);

        $this->assertEquals($payload, $mockFactory->retrieveContent());
    }

    public function testHttpReporterFails()
    {
        $context = TraceContext::createAsRoot();
        $localEndpoint = Endpoint::createAsEmpty();
        $span = Span::createFromContext($context, $localEndpoint);
        $span->start(Timestamp\now());
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error(Argument::containingString(HttpMockFactory::ERROR_MESSAGE))->shouldBeCalled();

        $mockFactory = HttpMockFactory::createAsFailing();
        $httpReporter = new Http([], $mockFactory, $logger->reveal());
        $httpReporter->report([$span]);
    }

    public function testHttpReportsEmptySpansSuccess()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error()->shouldNotBeCalled();

        $mockFactory = HttpMockFactory::createAsFailing();
        $httpReporter = new Http([], $mockFactory, $logger->reveal());
        $httpReporter->report([]);

        $this->assertEquals(0, $mockFactory->calledTimes());
    }
}
