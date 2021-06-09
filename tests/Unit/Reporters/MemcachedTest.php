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
use Exception;

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

    public function testReportError()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);
        $logger = $this->createMock(LoggerInterface::class);

        $memcachedClient->expects($this->exactly(1))
            ->method('ping')
            ->will($this->throwException(new Exception("Unable to connect")));

        $logger->expects($this->exactly(1))
            ->method('error');

        $memcached = new Memcached([], $memcachedClient, $logger);

        $context = TraceContext::createAsRoot();
        $localEndpoint = Endpoint::createAsEmpty();
        $span = Span::createFromContext($context, $localEndpoint);
        $now = Timestamp\now();
        $span->start($now);
        $span->setName('test');
        $payload = sprintf(self::PAYLOAD, $context->getSpanId(), $context->getTraceId(), $now);

        $this->assertNull($memcached->report([$span]));
    }

    public function testFlushingOfZeroSpans()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);

        $memcached = new Memcached([], $memcachedClient);

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

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error()->shouldNotBeCalled();
        $this->assertEquals($memcached->flush(), []);
    }

    public function testFlushingOfOneSpan()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);

        $memcached = new Memcached([], $memcachedClient);

        $memcachedClient->expects($this->exactly(1))
            ->method('ping')
            ->willReturn(true);

        $context = TraceContext::createAsRoot();
        $now = Timestamp\now();
        $payload = sprintf(self::PAYLOAD, $context->getSpanId(), $context->getTraceId(), $now);

        $memcachedClient->expects($this->exactly(1))
            ->method('get')
            ->with('zipkin_traces', null, MemcachedClient::GET_EXTENDED)
            ->willReturn([
                'cas' => 123,
                'value' => $payload
            ]);

        $memcachedClient->expects($this->exactly(1))
            ->method('cas')
            ->with('123', 'zipkin_traces', json_encode([]))
            ->willReturn(true);

        $memcachedClient->expects($this->exactly(1))
            ->method('quit')
            ->willReturn(true);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error()->shouldNotBeCalled();
        $this->assertEquals($memcached->flush(), json_decode($payload, true));
    }

    public function testFlushingOfOneSpanWithRetry()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);

        $memcached = new Memcached([], $memcachedClient);

        $memcachedClient->expects($this->once())
            ->method('ping')
            ->willReturn(true);

        $context = TraceContext::createAsRoot();
        $now = Timestamp\now();
        $payload = sprintf(self::PAYLOAD, $context->getSpanId(), $context->getTraceId(), $now);

        $memcachedClient->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['zipkin_traces', null, MemcachedClient::GET_EXTENDED],
                ['zipkin_traces', null, MemcachedClient::GET_EXTENDED]
            )->willReturnOnConsecutiveCalls(
                ['cas' => 123, 'value' => $payload],
                ['cas' => 124, 'value' => $payload]
            );

        $memcachedClient->expects($this->exactly(2))
            ->method('cas')
            ->withConsecutive(
                ['123', 'zipkin_traces', json_encode([])],
                ['124', 'zipkin_traces', json_encode([])]
            )->willReturnOnConsecutiveCalls(
                false,
                true
            );

        $memcachedClient->expects($this->once())
            ->method('quit')
            ->willReturn(true);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error()->shouldNotBeCalled();
        $this->assertEquals($memcached->flush(), json_decode($payload, true));
    }

    public function testFlushingError()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);
        $logger = $this->createMock(LoggerInterface::class);

        $memcached = new Memcached([], $memcachedClient, $logger);

        $memcachedClient->expects($this->exactly(1))
            ->method('ping')
            ->will($this->throwException(new Exception("Unable to connect")));

        $logger->expects($this->exactly(1))
            ->method('error');

        $this->assertEquals($memcached->flush(), []);
    }
}
