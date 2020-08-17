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

use PHPUnit\Framework\TestCase;
use Zipkin\Endpoint;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Recorder;
use Zipkin\Reporter;
use function Zipkin\Timestamp\now;
use Zipkin\Propagation\TraceContext;

final class RecorderTest extends TestCase
{
    public function testGetTimestampReturnsNullWhenThereIsNoSuchTraceContext()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $reporter = $this->prophesize(Reporter::class);
        $recorder = new Recorder(Endpoint::createAsEmpty(), $reporter->reveal(), false);
        $this->assertNull($recorder->getTimestamp($context));
    }

    public function testStartSuccess()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsEmpty());
        $reporter = $this->prophesize(Reporter::class);
        $recorder = new Recorder(Endpoint::createAsEmpty(), $reporter->reveal(), false);
        $timestamp = now();
        $recorder->start($context, $timestamp);
        $this->assertEquals($timestamp, $recorder->getTimestamp($context));
    }
}
