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

namespace ZipkinTests\Unit\Reporters;

use PHPUnit\Framework\TestCase;
use Zipkin\Reporters\InMemory;
use Zipkin\Samplers\BinarySampler;
use Zipkin\TracingBuilder;

final class InMemoryTest extends TestCase
{
    public function testReportOfSpans()
    {
        $reporter = new InMemory();
        $tracing = TracingBuilder::create()
            ->havingSampler(BinarySampler::createAsAlwaysSample())
            ->havingReporter($reporter)
            ->build();

        $span = $tracing->getTracer()->nextSpan();
        $span->start();
        $span->finish();

        $tracing->getTracer()->flush();
        $flushedSpans = $reporter->flush();

        $this->assertCount(1, $flushedSpans);
    }
}
