<?php

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
     * @return array|(Request|null)[][] the
     */
    abstract public static function delegateRequestsProvider(): array;

    /**
     * @dataProvider delegateRequestsProvider
     */
    public function testResponseIsCreatedSuccessfully($request)
    {
        list($response, $delegateResponse) = static::createResponse(202, [], null, $request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(202, $response->getStatusCode());
        $this->assertNullOrInstanceOf(Request::class, $request);
        $this->assertSame($request, $response->getRequest());
        $this->assertSame($delegateResponse, $response->unwrap());
    }

    private function assertNullOrInstanceOf(string $expected, $actual, string $message = '')
    {
        if ($actual === null) {
            return;
        }
        $this->assertInstanceOf($expected, $actual, $message);
    }
}
