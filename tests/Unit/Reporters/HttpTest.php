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
use Prophecy\Argument;
use PHPUnit\Framework\TestCase;

final class HttpTest extends TestCase
{
    const PAYLOAD = '[{"id":"%s","traceId":"%s",'
        . '"timestamp":%d,"name":"test","localEndpoint":{"serviceName":""}}]';

    public function testConstructorIsRetrocompatible()
    {
        $this->assertInstanceOf(Http::class, new Http());

        // old constructor
        $this->assertInstanceOf(Http::class, new Http(
            null,
            ['endpoint_url' => 'http://myzipkin:9411/api/v2/spans']
        ));
        $this->assertInstanceOf(Http::class, new Http(CurlFactory::create()));
        $this->assertInstanceOf(Http::class, new Http(
            CurlFactory::create(),
            ['endpoint_url' => 'http://myzipkin:9411/api/v2/spans']
        ));

        // new constructor
        $this->assertInstanceOf(Http::class, new Http(
            ['endpoint_url' => 'http://localhost:9411/api/v2/spans']
        ));
        $this->assertInstanceOf(Http::class, new Http(
            ['endpoint_url' => 'http://localhost:9411/api/v2/spans'],
            CurlFactory::create()
        ));

        try {
            new Http(1);
            $this->fail('Expected the constructor to fail.');
        } catch (TypeError $e) {
            $this->assertEquals(
                'Argument 1 passed to Zipkin\Reporters\Http::__construct must be of type array, integer given',
                $e->getMessage()
            );
        }
    }

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
