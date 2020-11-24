<?php

namespace ZipkinTests\Unit\Reporters;

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
        $span->setName('Test');
        $span->setKind('CLIENT');
        $remoteEndpoint = Endpoint::create('SERVICE2', null, '2001:0db8:85a3:0000:0000:8a2e:0370:7334', 3302);
        $span->setRemoteEndpoint($remoteEndpoint);
        $span->tag('test_key', 'test_value');
        $span->annotate($startTime + 100, 'test_annotation');
        $span->setError(new \RuntimeException('test_error'));
        $span->finish($startTime + 1000);
        $serializer = new JsonV2Serializer();
        $serializedSpans = $serializer->serialize([$span]);

        $expectedSerialization = '[{'
            . '"id":"186f11b67460db4d","traceId":"186f11b67460db4d","timestamp":1594044779509687,"name":"test",'
            . '"duration":1000,"localEndpoint":{"serviceName":"service1","ipv4":"192.168.0.11","port":3301},'
            . '"kind":"CLIENT",'
            . '"remoteEndpoint":{"serviceName":"service2","ipv6":"2001:0db8:85a3:0000:0000:8a2e:0370:7334","port":3302}'
            . ',"annotations":[{"value":"test_annotation","timestamp":1594044779509787}],'
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
            . '"id":"186f11b67460db4d","traceId":"186f11b67460db4d","timestamp":1594044779509688,"name":"test",'
            . '"duration":1000,"localEndpoint":{"serviceName":"service1","ipv4":"192.168.0.11","port":3301},'
            . '"tags":{"test_key":"test_value","error":"priority_error"}'
            . '}]';
        $this->assertEquals($expectedSerialization, $serializedSpans);
    }

    public function testStringValuesAreEscapedAndSerializedCorrectly()
    {
        $jsonValue = '{"name":"Kurt"}';
        $mutilineValue = <<<EOD
foo
bar
EOD;

        $context = TraceContext::create('186f11b67460db4e', '186f11b67460db4e');
        $localEndpoint = Endpoint::create('service1');
        $span = Span::createFromContext($context, $localEndpoint);
        $startTime = 1594044779509687;
        $span->start($startTime);
        $span->setName('My\Command');
        $span->tag('test_key_1', $jsonValue);
        $span->tag('test_key_2', $mutilineValue);
        $span->finish($startTime + 1000);
        $serializer = new JsonV2Serializer();
        $serializedSpans = $serializer->serialize([$span]);

        $expectedSerialization = '[{'
            . '"id":"186f11b67460db4e","traceId":"186f11b67460db4e","timestamp":1594044779509687,'
            . '"name":"my\\\\command","duration":1000,"localEndpoint":{"serviceName":"service1"},'
            . '"tags":{"test_key_1":"{\"name\":\"Kurt\"}","test_key_2":"foo\nbar"}'
            . '}]';

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $expectedSerialization = str_replace('\\n', '\\r\\n', $expectedSerialization);
        }

        $this->assertEquals($expectedSerialization, $serializedSpans);
    }
}
