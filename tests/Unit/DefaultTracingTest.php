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
use Zipkin\Tracer;
use Zipkin\Tracing;
use Zipkin\Endpoint;
use Zipkin\DefaultTracing;
use Zipkin\Propagation\B3;
use Zipkin\Reporters\Noop;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Propagation\Propagation;
use Zipkin\Propagation\CurrentTraceContext;

final class DefaultTracingTest extends TestCase
{
    public function testDefaultTracingCreationSuccess()
    {
        $localEndpoint = Endpoint::createAsEmpty();
        $reporter = new Noop();
        $sampler = BinarySampler::createAsNeverSample();
        $isNoop = $this->randomBool();

        $tracing = new DefaultTracing(
            $localEndpoint,
            $reporter,
            $sampler,
            false,
            new CurrentTraceContext,
            $isNoop,
            new B3,
            true
        );

        $this->assertInstanceOf(Tracing::class, $tracing);
        $this->assertInstanceOf(Tracer::class, $tracing->getTracer());
        $this->assertInstanceOf(Propagation::class, $tracing->getPropagation());
    }

    private function randomBool()
    {
        return (mt_rand(0, 1) === 1);
    }
}
