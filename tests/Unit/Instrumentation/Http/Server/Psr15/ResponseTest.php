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

namespace ZipkinTests\Unit\Instrumentation\Http\Server\Psr15;

use Zipkin\Instrumentation\Http\Client\Request;
use Zipkin\Instrumentation\Http\Client\Psr18\Response as Psr18Response;
use Zipkin\Instrumentation\Http\Client\Psr18\Request as Psr18Request;
use ZipkinTests\Unit\Instrumentation\Http\Client\BaseResponseTest;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request as Psr7Request;

final class ResponseTest extends BaseResponseTest
{
    /**
     * {@inheritdoc}
     */
    public static function createResponse(
        int $statusCode,
        $headers = [],
        $body = null,
        ?Request $request = null
    ): array {
        $delegateResponse = new Response($statusCode);
        $response = new Psr18Response($delegateResponse, $request);
        return [$response, $delegateResponse, $request];
    }

    /**
     * {@inheritdoc}
     */
    public static function requestsProvider(): array
    {
        return [
            [null],
            [new Psr18Request(new Psr7Request('GET', 'http://test.com/path'))],
        ];
    }
}
