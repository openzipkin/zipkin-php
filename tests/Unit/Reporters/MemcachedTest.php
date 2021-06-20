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
use Zipkin\Reporter;
use Prophecy\PhpUnit\ProphecyTrait;
use Exception;

final class MemcachedTest extends TestCase
{
    use ProphecyTrait;

    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function testReportError()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);
        $httpReporter = $this->createMock(Reporter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $memcached = new Memcached([], $httpReporter, $memcachedClient, $logger);

        $memcachedClient->expects($this->exactly(1))
            ->method('ping')
            ->will($this->throwException(new Exception("Unable to connect")));

        $logger->expects($this->exactly(1))
            ->method('error');

        $httpReporter->expects($this->exactly(1))
            ->method('report')
            ->with([new \stdClass()]);

        $memcached->report([new \stdClass()]);
    }

    public function testReportSuccessWithoutAggregatedSpans()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);
        $httpReporter = $this->createMock(Reporter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $memcached = new Memcached([], $httpReporter, $memcachedClient, $logger);

        $memcachedClient->expects($this->exactly(1))
            ->method('ping')
            ->willReturn(true);

        $memcachedClient->expects($this->exactly(1))
            ->method('get')
            ->with('zipkin_traces_spans', null, MemcachedClient::GET_EXTENDED)
            ->willReturn(null);


        $memcachedClient->expects($this->exactly(1))
            ->method('set')
            ->with('zipkin_traces_spans', serialize([new \stdClass()]));

        $memcachedClient->expects($this->exactly(1))
            ->method('quit')
            ->willReturn(true);

        $memcached->report([new \stdClass()]);
    }

    public function testReportSuccessWithSpans01()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);
        $httpReporter = $this->createMock(Reporter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $memcached = new Memcached([], $httpReporter, $memcachedClient, $logger);

        $memcachedClient->expects($this->exactly(1))
            ->method('ping')
            ->willReturn(true);

        $memcachedClient->expects($this->exactly(4))
            ->method('get')
            ->withConsecutive(
                ['zipkin_traces_spans', null, MemcachedClient::GET_EXTENDED],
                ['zipkin_traces_batch_ts', null, MemcachedClient::GET_EXTENDED],
                ['zipkin_traces_spans', null, MemcachedClient::GET_EXTENDED],
                ['zipkin_traces_batch_ts', null, MemcachedClient::GET_EXTENDED],
            )->willReturnOnConsecutiveCalls(
                ['cas' => 123, 'value' => serialize([new \stdClass()])],
                ['cas' => 127, 'value' => time()],
                ['cas' => 124, 'value' => serialize([new \stdClass()])],
                ['cas' => 129, 'value' => time()]
            );

        $memcachedClient->expects($this->exactly(2))
            ->method('compareAndSwap')
            ->withConsecutive(
                ['123', 'zipkin_traces_spans', serialize([new \stdClass(), new \stdClass()])],
                ['124', 'zipkin_traces_spans', serialize([new \stdClass(), new \stdClass()])]
            )->willReturnOnConsecutiveCalls(
                false,
                true
            );

        $memcachedClient->expects($this->once())
            ->method('quit')
            ->willReturn(true);

        $memcached->report([new \stdClass()]);
    }

    public function testReportSuccessWithSpans02()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);
        $httpReporter = $this->createMock(Reporter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $memcached = new Memcached([], $httpReporter, $memcachedClient, $logger);

        $memcachedClient->expects($this->exactly(2))
            ->method('ping')
            ->willReturn(true);

        $memcachedClient->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['zipkin_traces_spans', null, MemcachedClient::GET_EXTENDED],
                ['zipkin_traces_batch_ts', null, MemcachedClient::GET_EXTENDED],
                ['zipkin_traces_batch_ts', null, MemcachedClient::GET_EXTENDED],
            )->willReturnOnConsecutiveCalls(
                ['cas' => 123, 'value' => serialize([new \stdClass()])],
                ['cas' => 127, 'value' => time() - 900],
                null
            );

        $memcachedClient->expects($this->exactly(1))
            ->method('compareAndSwap')
            ->with('123', 'zipkin_traces_spans', serialize([]))
            ->willReturn(true);

        $memcachedClient->expects($this->exactly(2))
            ->method('quit')
            ->willReturn(true);

        $httpReporter->expects($this->exactly(1))
            ->method('report')
            ->with([new \stdClass(), new \stdClass()]);

        $memcached->report([new \stdClass()]);
    }

    public function testFlushingOfOneSpanWithRetry()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);
        $httpReporter = $this->createMock(Reporter::class);
        $memcached = new Memcached([], $httpReporter, $memcachedClient);

        $memcachedClient->expects($this->once())
            ->method('ping')
            ->willReturn(true);

        $memcachedClient->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['zipkin_traces_spans', null, MemcachedClient::GET_EXTENDED],
                ['zipkin_traces_spans', null, MemcachedClient::GET_EXTENDED]
            )->willReturnOnConsecutiveCalls(
                ['cas' => 123, 'value' => serialize([new \stdClass()])],
                ['cas' => 124, 'value' => serialize([new \stdClass()])]
            );

        $memcachedClient->expects($this->exactly(2))
            ->method('compareAndSwap')
            ->withConsecutive(
                ['123', 'zipkin_traces_spans', serialize([])],
                ['124', 'zipkin_traces_spans', serialize([])]
            )->willReturnOnConsecutiveCalls(
                false,
                true
            );

        $memcachedClient->expects($this->once())
            ->method('quit')
            ->willReturn(true);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error()->shouldNotBeCalled();
        $this->assertEquals($memcached->flush(), [new \stdClass()]);
    }

    public function testFlushingOfOneSpan()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);
        $httpReporter = $this->createMock(Reporter::class);
        $memcached = new Memcached([], $httpReporter, $memcachedClient);

        $memcachedClient->expects($this->exactly(1))
            ->method('ping')
            ->willReturn(true);

        $memcachedClient->expects($this->exactly(1))
            ->method('get')
            ->with('zipkin_traces_spans', null, MemcachedClient::GET_EXTENDED)
            ->willReturn([
                'cas' => 123,
                'value' => serialize([new \stdClass()])
            ]);

        $memcachedClient->expects($this->exactly(1))
            ->method('compareAndSwap')
            ->with('123', 'zipkin_traces_spans', serialize([]))
            ->willReturn(true);

        $memcachedClient->expects($this->exactly(1))
            ->method('quit')
            ->willReturn(true);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error()->shouldNotBeCalled();
        $this->assertEquals($memcached->flush(), [new \stdClass()]);
    }

    public function testFlushingOfZeroSpans()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);
        $httpReporter = $this->createMock(Reporter::class);
        $memcached = new Memcached([], $httpReporter, $memcachedClient);

        $memcachedClient->expects($this->exactly(1))
            ->method('ping')
            ->willReturn(true);

        $memcachedClient->expects($this->exactly(1))
            ->method('get')
            ->with('zipkin_traces_spans', null, MemcachedClient::GET_EXTENDED)
            ->willReturn(false);

        $memcachedClient->expects($this->exactly(1))
            ->method('quit')
            ->willReturn(true);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error()->shouldNotBeCalled();
        $this->assertEquals($memcached->flush(), []);
    }

    public function testFlushingError()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);
        $httpReporter = $this->createMock(Reporter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $memcached = new Memcached([], $httpReporter, $memcachedClient, $logger);

        $memcachedClient->expects($this->exactly(1))
            ->method('ping')
            ->will($this->throwException(new Exception("Unable to connect")));

        $logger->expects($this->exactly(1))
            ->method('error');

        $this->assertEquals($memcached->flush(), []);
    }

    public function testDisabledPatchInterval()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);
        $httpReporter = $this->createMock(Reporter::class);
        $memcached = new Memcached(['batch_interval' => -1], $httpReporter, $memcachedClient);

        $this->assertEquals(
            $this->invokeMethod($memcached, 'isBatchIntervalPassed', []),
            false
        );
    }

    public function testEnabledPatchInterval01()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);
        $httpReporter = $this->createMock(Reporter::class);
        $memcached = new Memcached(['batch_interval' => 60], $httpReporter, $memcachedClient);

        $memcachedClient->expects($this->exactly(1))
            ->method('get')
            ->with('zipkin_traces_batch_ts', null, MemcachedClient::GET_EXTENDED)
            ->willReturn(null);

        $this->assertEquals(
            $this->invokeMethod($memcached, 'isBatchIntervalPassed', []),
            true
        );
    }

    public function testEnabledPatchInterval02()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);
        $httpReporter = $this->createMock(Reporter::class);
        $memcached = new Memcached(['batch_interval' => 60], $httpReporter, $memcachedClient);

        $memcachedClient->expects($this->exactly(1))
            ->method('get')
            ->with('zipkin_traces_batch_ts', null, MemcachedClient::GET_EXTENDED)
            ->willReturn([
                'cas' => 123,
                'value' => time() - 90
            ]);

        $this->assertEquals(
            $this->invokeMethod($memcached, 'isBatchIntervalPassed', []),
            true
        );
    }

    public function testEnabledPatchInterval03()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);
        $httpReporter = $this->createMock(Reporter::class);
        $memcached = new Memcached(['batch_interval' => 60], $httpReporter, $memcachedClient);

        $memcachedClient->expects($this->exactly(1))
            ->method('get')
            ->with('zipkin_traces_batch_ts', null, MemcachedClient::GET_EXTENDED)
            ->willReturn([
                'cas' => 123,
                'value' => time() - 40
            ]);

        $this->assertEquals(
            $this->invokeMethod($memcached, 'isBatchIntervalPassed', []),
            false
        );
    }

    public function testResetBatchInterval()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);
        $httpReporter = $this->createMock(Reporter::class);
        $memcached = new Memcached(['batch_interval' => -1], $httpReporter, $memcachedClient);

        $this->assertEquals(
            $this->invokeMethod($memcached, 'resetBatchInterval', []),
            false
        );
    }

    public function testResetBatchIntervalError()
    {
        $memcachedClient = $this->createMock(MemcachedClient::class);
        $httpReporter = $this->createMock(Reporter::class);
        $logger = $this->createMock(LoggerInterface::class);
        $memcached = new Memcached([], $httpReporter, $memcachedClient, $logger);

        $memcachedClient->expects($this->exactly(1))
            ->method('ping')
            ->will($this->throwException(new Exception("Unable to connect")));

        $logger->expects($this->exactly(1))
            ->method('error');

        $this->assertEquals(
            $this->invokeMethod($memcached, 'resetBatchInterval', []),
            true
        );
    }
}
