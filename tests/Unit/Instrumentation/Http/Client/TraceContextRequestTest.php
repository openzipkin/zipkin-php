<?php

declare(strict_types=1);

namespace ZipkinTests\Instrumentation\Http;

use Zipkin\Propagation\TraceContext;
use Zipkin\Instrumentation\Http\Client\Psr\TraceContextRequest;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Request;

final class TraceContextRequestTest extends TestCase
{
    public function testWrapRequest()
    {
        $request = new Request('GET', 'http://mytest/things');
        $context = TraceContext::createAsRoot();
        $traceContextRequest = TraceContextRequest::wrap($request, $context);
        $this->assertEquals('GET', $traceContextRequest->getMethod());
        $this->assertEquals('http://mytest/things', $traceContextRequest->getUri()->__toString());
        $this->assertTrue($context->isEqual($traceContextRequest->getTraceContext()));
    }

    public function testWithMethodSuccess()
    {
        $request = new Request('GET', 'http://mytest/things');
        $context = TraceContext::createAsRoot();
        $traceContextRequest = TraceContextRequest::wrap($request, $context);
        $traceContextRequestModified = $traceContextRequest->withMethod('POST');
        $this->assertNotEquals(spl_object_hash($traceContextRequest), spl_object_hash($traceContextRequestModified));
        $actualValue = $traceContextRequestModified->getMethod();
        $this->assertEquals('POST', $actualValue);
    }
}
