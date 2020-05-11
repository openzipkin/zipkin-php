<?php

namespace ZipkinTests\Unit\Reporters;

use TypeError;
use Zipkin\Endpoint;
use Prophecy\Argument;
use Zipkin\Recording\Span;
use Zipkin\Reporters\Http;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Zipkin\Propagation\TraceContext;
use Zipkin\Reporters\Http\CurlFactory;

final class HttpTest extends TestCase
{
    const PAYLOAD = '[{"id":"%s","name":null,"traceId":"%s","parentId":null,'
        . '"timestamp":null,"duration":null,"debug":false,"localEndpoint":{"serviceName":""}}]';

    public function testConstructorIsRetrocompatible()
    {
        $this->assertInstanceOf(Http::class, new Http());

        // old constructor
        $this->assertInstanceOf(Http::class, new Http(CurlFactory::create()));
        $this->assertInstanceOf(Http::class, new Http(
            CurlFactory::create(),
            ['endpoint_url' => 'http://myzipkin:9411/api/v2/spans',]
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
            new Http(null);
            $this->fail('Expected the constructor to fail.');
        } catch (TypeError $e) {
            $this->assertEquals(
                'Argument 1 passed to Zipkin\Reporters\Http::__construct must be of type array, null given',
                $e->getMessage()
            );
        }
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
        $httpReporter = new Http([], $mockFactory, $logger->reveal());
        $httpReporter->report([$span]);

        $this->assertEquals($payload, $mockFactory->retrieveContent());
    }

    public function testHttpReporterFails()
    {
        $context = TraceContext::createAsRoot();
        $localEndpoint = Endpoint::createAsEmpty();
        $span = Span::createFromContext($context, $localEndpoint);
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
