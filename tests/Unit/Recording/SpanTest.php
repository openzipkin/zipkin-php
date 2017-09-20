<?php

namespace ZipkinTests\Unit\Recording;

use PHPUnit_Framework_TestCase;
use Zipkin\Endpoint;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Recording\Span;
use Zipkin\Timestamp;
use Zipkin\TraceContext;

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

        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $context->setSpanId($spanId);
        $context->setParentId($parentId);
        $context->setTraceId($traceId);

        $endpoint = Endpoint::create('test_service_name', '127.0.0.1', null, 3333);
        $span = Span::createFromContext($context, $endpoint);
        $timestamp = Timestamp\now();

        $span->start($timestamp);
        $span->setName('test_name');
        $span->annotate($timestamp, 'test_annotation');
        $span->tag('test_key', 'test_value');
        $span->finish($timestamp + 100);

        $expectedSpanArray = [
            'id' => $spanId,
            'traceId' => $traceId,
            'parentId' => $parentId,
            'timestamp' => $timestamp,
            'name' => 'test_name',
            'duration' => 100,
            'debug' => false,
            'annotations' => [
                [
                    'value' => 'test_annotation',
                    'timestamp' => $timestamp,
                    'endpoint' => [
                        'serviceName' => 'test_service_name',
                        'ipv4' => '127.0.0.1',
                        'port' => 3333,
                    ],
                ],
            ],
            'binaryAnnotations' => [
                [
                    'key' => 'test_key',
                    'value' => 'test_value',
                    'endpoint' => [
                        'serviceName' => 'test_service_name',
                        'ipv4' => '127.0.0.1',
                        'port' => 3333,
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedSpanArray, $span->toArray());
    }
}
