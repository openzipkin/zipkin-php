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

namespace ZipkinTests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zipkin\Endpoint;
use Zipkin\RealSpan;
use Zipkin\Recorder;
use function Zipkin\Timestamp\now;
use const Zipkin\Annotations\WIRE_SEND;
use Zipkin\Propagation\TraceContext;

final class RealSpanTest extends TestCase
{
    private const TEST_NAME = 'test_span';
    private const TEST_KIND = 'ab';
    private const TEST_START_TIMESTAMP = 1472470996199000;

    public function testCreateRealSpanSuccess()
    {
        $context = TraceContext::createAsRoot();
        $recorder = $this->prophesize(Recorder::class);
        $span = new RealSpan($context, $recorder->reveal());
        $this->assertEquals($context, $span->getContext());
    }

    public function testStartSuccess()
    {
        $context = TraceContext::createAsRoot();
        $recorder = $this->prophesize(Recorder::class);
        $recorder->start($context, self::TEST_START_TIMESTAMP)->shouldBeCalled();
        $span = new RealSpan($context, $recorder->reveal());
        $span->start(self::TEST_START_TIMESTAMP);
    }

    public function testSetNameSuccess()
    {
        $context = TraceContext::createAsRoot();
        $recorder = $this->prophesize(Recorder::class);
        $recorder->setName($context, self::TEST_NAME)->shouldBeCalled();
        $span = new RealSpan($context, $recorder->reveal());
        $span->setName(self::TEST_NAME);
    }

    public function testSetKindSuccess()
    {
        $context = TraceContext::createAsRoot();
        $recorder = $this->prophesize(Recorder::class);
        $recorder->setKind($context, self::TEST_KIND)->shouldBeCalled();
        $span = new RealSpan($context, $recorder->reveal());
        $span->setKind(self::TEST_KIND);
    }

    public function testSetRemoteEndpointSuccess()
    {
        $context = TraceContext::createAsRoot();
        $remoteEndpoint = Endpoint::createAsEmpty();
        $recorder = $this->prophesize(Recorder::class);
        $recorder->setRemoteEndpoint($context, $remoteEndpoint)->shouldBeCalled();
        $span = new RealSpan($context, $recorder->reveal());
        $span->setRemoteEndpoint($remoteEndpoint);
    }

    public function testAnnotateSuccess()
    {
        $timestamp = now();
        $value = WIRE_SEND;
        $context = TraceContext::createAsRoot();
        $recorder = $this->prophesize(Recorder::class);
        $recorder->annotate($context, $timestamp, $value)->shouldBeCalled();
        $span = new RealSpan($context, $recorder->reveal());
        $span->annotate($value, $timestamp);
    }

    public function testAnnotateFailsDueToInvalidTimestamp()
    {
        $this->expectException(InvalidArgumentException::class);
        $timestamp = -1;
        $value = WIRE_SEND;
        $context = TraceContext::createAsRoot();
        $recorder = $this->prophesize(Recorder::class);
        $recorder->annotate($context, $timestamp, $value)->shouldNotBeCalled();
        $span = new RealSpan($context, $recorder->reveal());
        $this->expectException(InvalidArgumentException::class);
        $span->annotate($value, $timestamp);
    }

    public function testStartRealSpanFailsDueToInvalidTimestamp()
    {
        $this->expectException(InvalidArgumentException::class);
        $context = TraceContext::createAsRoot();
        $recorder = $this->prophesize(Recorder::class);
        $span = new RealSpan($context, $recorder->reveal());
        $span->start(-1);
    }
}
