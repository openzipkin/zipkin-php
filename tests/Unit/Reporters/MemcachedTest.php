<?php

namespace ZipkinTests\Unit\Reporters;

use PHPUnit\Framework\TestCase;
use Zipkin\Reporters\Memcached;
use Zipkin\Reporters\Aggregation\MemcachedClient;
use Zipkin\Timestamp;
use Zipkin\Recording\Span;
use Zipkin\Propagation\TraceContext;
use Zipkin\Endpoint;
use Psr\Log\LoggerInterface;
use Prophecy\PhpUnit\ProphecyTrait;

final class MemcachedTest extends TestCase
{
    use ProphecyTrait;

    const PAYLOAD = '[{"id":"%s","traceId":"%s",'
        . '"timestamp":%d,"name":"test","localEndpoint":{"serviceName":""}}]';

    public function testReportOfSpans()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);

        $memcachedClient->expects($this->exactly(1))
            ->method('ping')
            ->willReturn(true);

        $memcachedClient->expects($this->exactly(1))
            ->method('get')
            ->with('zipkin_traces', null, MemcachedClient::GET_EXTENDED)
            ->willReturn(false);

        $memcachedClient->expects($this->exactly(1))
            ->method('quit')
            ->willReturn(true);

        $memcached = new Memcached([], $memcachedClient);

        $context = TraceContext::createAsRoot();
        $localEndpoint = Endpoint::createAsEmpty();
        $span = Span::createFromContext($context, $localEndpoint);
        $now = Timestamp\now();
        $span->start($now);
        $span->setName('test');
        $payload = sprintf(self::PAYLOAD, $context->getSpanId(), $context->getTraceId(), $now);

        $memcachedClient->expects($this->exactly(1))
            ->method('set')
            ->with('zipkin_traces', $payload)
            ->willReturn(true);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error()->shouldNotBeCalled();
        $this->assertNull($memcached->report([$span]));
    }
}
