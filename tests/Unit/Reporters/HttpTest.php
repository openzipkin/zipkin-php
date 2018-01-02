<?php

namespace ZipkinTests\Unit\Reporters;

use PHPUnit_Framework_TestCase;
use Zipkin\Endpoint;
use Zipkin\Propagation\TraceContext;
use Zipkin\Recording\Span;
use Zipkin\Reporters\Http;

final class HttpTest extends PHPUnit_Framework_TestCase
{
    const PAYLOAD = '[{"id":"%s","name":null,"traceId":"%s","parentId":null,'
        . '"timestamp":null,"duration":null,"debug":false,"localEndpoint":{"serviceName":""}}]';

    public function testHttpReporterSuccess()
    {
        $context = TraceContext::createAsRoot();
        $localEndpoint = Endpoint::createAsEmpty();
        $span = Span::createFromContext($context, $localEndpoint);

        $mockFactory = new HttpMockFactory();
        $httpReporter = new Http($mockFactory);
        $httpReporter->report([$span]);

        $this->assertEquals(
            sprintf(self::PAYLOAD, $context->getSpanId(), $context->getTraceId()),
            $mockFactory->retrieveContent()
        );
    }
}
