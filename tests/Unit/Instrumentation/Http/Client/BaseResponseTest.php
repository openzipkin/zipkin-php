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

declare(strict_types=1);

namespace ZipkinTests\Unit\Instrumentation\Http\Client;

use Zipkin\Instrumentation\Http\Client\Response;
use Zipkin\Instrumentation\Http\Client\Request;
use PHPUnit\Framework\TestCase;

abstract class BaseResponseTest extends TestCase
{
    /**
     * @return array|mixed[] including:
     * - Zipkin\Instrumentation\Http\Client\Response the response being.
     * - mixed the delegated response.
     * - Zipkin\Instrumentation\Http\Client\Request the request originating
     *   originating the response.
     */
    abstract public static function createResponse(
        int $statusCode,
        $headers = [],
        $body = null,
        Request $request = null
    ): array;

    /**
     * @return (Request|null)[][] the
     */
    abstract public static function requestsProvider(): array;

    /**
     * @dataProvider requestsProvider
     */
    public function testResponseIsCreatedSuccessfully(?Request $request): void
    {
        /**
         * @var Response $response
         */
        list($response, $delegateResponse) = static::createResponse(202, [], null, $request);
        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertSame($request, $response->getRequest());
        $this->assertSame($delegateResponse, $response->unwrap());
    }
}
