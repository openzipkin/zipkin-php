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

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Zipkin\Propagation\RequestHeaders;

final class RequestHeadersTest extends TestCase
{
    const TEST_KEY = 'key';
    const TEST_VALUE = 'value';

    public function testGetReturnsNullIfKeyDoesNotExists()
    {
        $request = new Request('GET', '/');
        $requestHeaders = new RequestHeaders;
        $value = $requestHeaders->get($request, self::TEST_KEY);
        $this->assertNull($value);
    }

    public function testGetReturnsTheExpectedValue()
    {
        $request = new Request('GET', '/', [self::TEST_KEY => self::TEST_VALUE]);
        $requestHeaders = new RequestHeaders;
        $value = $requestHeaders->get($request, self::TEST_KEY);
        $this->assertEquals(self::TEST_VALUE, $value);
    }

    public function testPutWritesTheExpectedValue()
    {
        $request = new Request('GET', '/');
        $requestHeaders = new RequestHeaders;
        $requestHeaders->put($request, self::TEST_KEY, self::TEST_VALUE);
        $value = $requestHeaders->get($request, self::TEST_KEY);
        $this->assertEquals(self::TEST_VALUE, $value);
    }

    public function testPutOverridesWithTheExpectedValue()
    {
        $request = new Request('GET', '/', [self::TEST_KEY => 'foobar']);
        $requestHeaders = new RequestHeaders();
        $requestHeaders->put($request, self::TEST_KEY, self::TEST_VALUE);
        $value = $requestHeaders->get($request, self::TEST_KEY);
        $this->assertEquals(self::TEST_VALUE, $value);
    }
}
