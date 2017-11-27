<?php

namespace ZipkinTests\Unit\Recording;

use PHPUnit_Framework_TestCase;
use Zipkin\Kind;
use Zipkin\Endpoint;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Recording\Span;
use Zipkin\Timestamp;
use Zipkin\Propagation\TraceContext;

final class SpanTest extends PHPUnit_Framework_TestCase
{
    public function testCreateSpanAsRootSuccess()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $endpoint = Endpoint::createAsEmpty();
        $span = Span::createFromContext($context, $endpoint);
        $this->assertInstanceOf(Span::class, $span);
    }

    public function testStartSpanSuccess()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $endpoint = Endpoint::createAsEmpty();
        $span = Span::createFromContext($context, $endpoint);
        $timestamp = Timestamp\now();
        $span->start($timestamp);
        $this->assertEquals($timestamp, $span->getTimestamp());
    }

    public function testConvertingSpanToArrayHasTheExpectedValues()
    {
        $spanId = 'e463f94de30144fa';
        $traceId = 'e463f94de30144fa';
        $parentId = 'e463f94de30144fb';

        $context = TraceContext::create($traceId, $spanId, $parentId);

        $localEndpoint = Endpoint::create('test_service_name', '127.0.0.1', null, 3333);
        $span = Span::createFromContext($context, $localEndpoint);
        $timestamp = Timestamp\now();

        $span->start($timestamp);
        $span->setKind(Kind\CLIENT);
        $span->setName('test_name');
        $span->annotate($timestamp, 'test_annotation');
        $span->tag('test_key', 'test_value');
        $span->finish($timestamp + 100);

        $expectedSpanArray = [
            'id' => $spanId,
            'kind' => 'CLIENT',
            'traceId' => $traceId,
            'parentId' => $parentId,
            'timestamp' => $timestamp,
            'name' => 'test_name',
            'duration' => 100,
            'debug' => false,
            'localEndpoint' => [
                'serviceName' => 'test_service_name',
                'ipv4' => '127.0.0.1',
                'port' => 3333,
            ],
            'annotations' => [
                [
                    'value' => 'test_annotation',
                    'timestamp' => $timestamp,
                ],
            ],
            'tags' => [
                'test_key' => 'test_value',
            ],
        ];

        $this->assertEquals($expectedSpanArray, $span->toArray());
    }
}
