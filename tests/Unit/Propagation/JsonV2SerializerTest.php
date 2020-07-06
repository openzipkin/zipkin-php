<?php

namespace ZipkinTests\Unit\Propagation;

use Zipkin\Reporters\JsonV2Serializer;
use Zipkin\Recording\Span;
use Zipkin\Propagation\TraceContext;
use Zipkin\Endpoint;
use PHPUnit\Framework\TestCase;

final class JsonV2SerializerTest extends TestCase
{
    public function testSpanIsSerializedSuccessfully()
    {
        $context = TraceContext::create('186f11b67460db4d', '186f11b67460db4d');
        $localEndpoint = Endpoint::create('service1', '192.168.0.11', null, 3301);
        $span = Span::createFromContext($context, $localEndpoint);
        $startTime = 1594044779509687;
        $span->start($startTime);
        $span->setName('test');
        $span->setKind('CLIENT');
        $remoteEndpoint = Endpoint::create('service2', '192.168.0.12', null, 3302);
        $span->setRemoteEndpoint($remoteEndpoint);
        $span->tag('test_key', 'test_value');
        $span->annotate($startTime + 100, 'test_annotarion');
        $span->setError(new \RuntimeException('test_error'));
        $span->finish($startTime + 1000);
        $serializer = new JsonV2Serializer();
        $serializedSpans = $serializer->serialize([$span]);

        $expectedSerialization = '[{'
            . '"id":"186f11b67460db4d","name":"test","traceId":"186f11b67460db4d","timestamp":1594044779509687,'
            . '"duration":1000,"localEndpoint":{"serviceName":"service1","ipv4":"192.168.0.11","port":3301},'
            . '"debug":"CLIENT","remoteEndpoint":{"serviceName":"service2","ipv4":"192.168.0.12","port":3302},'
            . '"annotations":[{"value":"test_annotarion","timestamp":1594044779509787}],'
            . '"tags":{"test_key":"test_value","error":"test_error"}'
            . '}]';
        $this->assertEquals($expectedSerialization, $serializedSpans);
    }

    public function testErrorTagIsNotClobberedBySpanError()
    {
        $context = TraceContext::create('186f11b67460db4d', '186f11b67460db4d');
        $localEndpoint = Endpoint::create('service1', '192.168.0.11', null, 3301);
        $span = Span::createFromContext($context, $localEndpoint);
        $startTime = 1594044779509688;
        $span->start($startTime);
        $span->setName('test');
        $span->tag('test_key', 'test_value');
        $span->tag('error', 'priority_error');
        $span->setError(new \RuntimeException('test_error'));
        $span->finish($startTime + 1000);
        $serializer = new JsonV2Serializer();
        $serializedSpans = $serializer->serialize([$span]);

        $expectedSerialization = '[{'
            . '"id":"186f11b67460db4d","name":"test","traceId":"186f11b67460db4d","timestamp":1594044779509688,'
            . '"duration":1000,"localEndpoint":{"serviceName":"service1","ipv4":"192.168.0.11","port":3301},'
            . '"tags":{"test_key":"test_value","error":"priority_error"}'
            . '}]';
        $this->assertEquals($expectedSerialization, $serializedSpans);
    }
}
