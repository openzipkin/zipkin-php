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

namespace ZipkinTests\Unit\Propagation;

use PHPUnit\Framework\TestCase;
use function Zipkin\Propagation\Id\generateNextId;
use function Zipkin\Propagation\Id\generateTraceIdWith128bits;
use function Zipkin\Propagation\Id\isValidSpanId;
use function Zipkin\Propagation\Id\isValidTraceId;

final class IdTest extends TestCase
{
    public function testNextIdSuccess()
    {
        $nextId = generateNextId();
        $this->assertTrue(ctype_xdigit($nextId));
        $this->assertEquals(16, strlen($nextId));
    }

    public function testTraceIdWith128bitsSuccess()
    {
        $nextId = generateTraceIdWith128bits();
        $this->assertTrue(ctype_xdigit($nextId));
        $this->assertEquals(32, strlen($nextId));
    }

    /**
     * @dataProvider spanIdsDataProvider
     */
    public function testIsValidSpanIdSuccess($spanId, $isValid)
    {
        $this->assertEquals($isValid, isValidSpanId($spanId));
    }

    public function spanIdsDataProvider()
    {
        return [
            ['', false],
            ['1', true],
            ['50d1e105a060618', true],
            ['050d1e105a060618', true],
            ['g50d1e105a060618', false],
            ['050d1e105a060618a', false],
        ];
    }

    /**
     * @dataProvider traceIdsDataProvider
     */
    public function testIsValidTraceIdSuccess($traceId, $isValid)
    {
        $this->assertEquals($isValid, isValidTraceId($traceId));
    }

    public function traceIdsDataProvider()
    {
        return [
            ['', false],
            ['1', true],
            ['050d1e105a060618', true],
            ['g50d1e105a060618', false],
            ['050d1e105a060618050d1e105a060618', true],
            ['050d1e105a060618g50d1e105a060618', false],
            ['050d1e105a060618050d1e105a060618a', false],
        ];
    }
}
