<?php

namespace ZipkinTests\Unit\Reporters\Log;

use Zipkin\Reporters\Log\LogSerializer;
use Zipkin\Recording\Span;
use Zipkin\Propagation\TraceContext;
use Zipkin\Endpoint;
use Prophecy\PhpUnit\ProphecyTrait;
use PHPUnit\Framework\TestCase;

final class LogSerializerTest extends TestCase
{
    use ProphecyTrait;

    public function testSpanIsSerializedSuccessfully()
    {
        $context = TraceContext::create('186f11b67460db4d', '186f11b67460db4d');
        $localEndpoint = Endpoint::create('service1', '192.168.0.11', null, 3301);
        $span = Span::createFromContext($context, $localEndpoint);
        $startTime = 1594044779509687;
        $span->start($startTime);
        $span->setName('Test');
        $span->setKind('CLIENT');
        $remoteEndpoint = Endpoint::create('SERVICE2', null, '2001:0db8:85a3:0000:0000:8a2e:0370:7334', 3302);
        $span->setRemoteEndpoint($remoteEndpoint);
        $span->tag('test_key', 'test_value');
        $span->annotate($startTime + 100, 'test_annotation');
        $span->setError(new \RuntimeException('test_error'));
        $span->finish($startTime + 1000);

        $serializer = new LogSerializer();
        $serializedSpans = $serializer->serialize([$span]);

        $expectedSerialization = <<<TEXT
Name: Test
TraceID: 186f11b67460db4d
SpanID: 186f11b67460db4d
Timestamp: 1594044779509687
Duration: 1000
Kind: CLIENT
LocalEndpoint: service1
Tags:
    test_key: test_value
Annotations:
    - timestamp: 1594044779509787
      value: test_annotation
RemoteEndpoint: SERVICE2

TEXT;
        $this->assertEquals($expectedSerialization, $serializedSpans);
    }
}
