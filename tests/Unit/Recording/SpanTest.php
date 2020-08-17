<?php

/**
 * Copyright 2020 OpenZipkin Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace ZipkinTests\Unit\Recording;

use PHPUnit\Framework\TestCase;
use Zipkin\Kind;
use Zipkin\Endpoint;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Recording\Span;
use function Zipkin\Timestamp\now;
use Zipkin\Propagation\TraceContext;

final class SpanTest extends TestCase
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
        $timestamp = now();
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
        $timestamp = now();

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
