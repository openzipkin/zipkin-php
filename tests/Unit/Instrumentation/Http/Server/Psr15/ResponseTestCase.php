<?php

declare(strict_types=1);

namespace ZipkinTests\Unit\Instrumentation\Http\Server\Psr15;

use Zipkin\Instrumentation\Http\Server\Request;
use Zipkin\Instrumentation\Http\Server\Psr15\Response as Psr15Response;
use Zipkin\Instrumentation\Http\Server\Psr15\Request as Psr15Request;
use ZipkinTests\Unit\Instrumentation\Http\Server\BaseResponseTestCase;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Request as Psr7Request;

final class ResponseTestCase extends BaseResponseTestCase
{
    /**
     * {@inheritdoc}
     */
    public static function createResponse(
        int $statusCode,
        $headers = [],
        $body = null,
        ?Request $request = null,
        ?string $route = null
    ): array {
        $delegateResponse = new Response($statusCode);
        $response = new Psr15Response($delegateResponse, $request);
        return [$response, $delegateResponse, $request, null];
    }

    /**
     * {@inheritdoc}
     */
    public static function requestsProvider(): array
    {
        return [
            [null],
            [new Psr15Request(new Psr7Request('GET', 'http://test.com/path'))],
        ];
    }
}
