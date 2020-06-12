<?php

namespace ZipkinTests\Unit\Propagation;

use PHPUnit\Framework\TestCase;
use Zipkin\Propagation\B3;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Propagation\ServerHeaders;
use Zipkin\Propagation\TraceContext;
use function Zipkin\Propagation\Id\generateNextId;

final class ServerHeadersTest extends TestCase
{
    public function testDoesntCrashIfNoHeaders()
    {
        $server = [];

        $propagation = new B3();
        $extractor = $propagation->getExtractor(new ServerHeaders);
        $extracted = $extractor($server);

        $this->assertTrue($extracted instanceof DefaultSamplingFlags);
    }

    public function testCorrectlyParsesHeaders()
    {
        $traceId = generateNextId();
        $spanId = generateNextId();
        $parentSpanId = generateNextId();
        $server = [
            'HTTP_X_B3_TRACEID' => $traceId,
            'HTTP_X_B3_SPANID' => $spanId,
            'HTTP_X_B3_PARENTSPANID' => $parentSpanId,
            'HTTP_X_B3_SAMPLED' => '1',
        ];

        $propagation = new B3();
        $extractor = $propagation->getExtractor(new ServerHeaders);
        $extracted = $extractor($server);

        $this->assertTrue($extracted instanceof TraceContext);
        $this->assertEquals($traceId, $extracted->getTraceId());
        $this->assertEquals($spanId, $extracted->getSpanId());
        $this->assertEquals($parentSpanId, $extracted->getParentId());
    }
}
