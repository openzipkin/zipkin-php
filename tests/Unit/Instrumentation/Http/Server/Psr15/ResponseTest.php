<?php

declare(strict_types=1);

namespace ZipkinTests\Unit\Instrumentation\Http\Server\Psr15;

use Zipkin\Instrumentation\Http\Client\Psr18\Response as Psr18Response;
use Zipkin\Instrumentation\Http\Client\Psr18\Request;
use ZipkinTests\Unit\Instrumentation\Http\Client\BaseResponseTest;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request as Psr7Request;

final class ResponseTest extends BaseResponseTest
{
    public static function createResponse(int $statusCode, $headers = [], $body = null, $request = null): array
    {
        $delegateResponse = new Response($statusCode);
        $response = new Psr18Response($delegateResponse, $request);
        return [$response, $delegateResponse, $request];
    }

    public static function delegateRequestsProvider(): array
    {
        return [
            [null],
            [new Request(new Psr7Request('GET', 'http://test.com/path'))],
        ];
    }
}
