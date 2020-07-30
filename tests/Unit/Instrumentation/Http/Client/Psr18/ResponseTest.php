<?php

declare(strict_types=1);

namespace ZipkinTests\Unit\Instrumentation\Http\Client\Psr18;

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
        Request $request = null
    ): array {
        $delegateResponse = new Response($statusCode);
        $response = new Psr18Response($delegateResponse, $request);
        return [$response, $delegateResponse, $request];
    }

    /**
     * {@inheritdoc}
     */
    public static function delegateRequestsProvider(): array
    {
        return [
            [null],
            [new Psr18Request(new Psr7Request('GET', 'http://test.com/path'))],
        ];
    }
}
