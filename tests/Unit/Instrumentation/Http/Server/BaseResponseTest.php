<?php

declare(strict_types=1);

namespace ZipkinTests\Unit\Instrumentation\Http\Server;

use Zipkin\Instrumentation\Http\Server\Response;
use Zipkin\Instrumentation\Http\Server\Request;
use PHPUnit\Framework\TestCase;

abstract class BaseResponseTest extends TestCase
{
    /**
     * @return mixed[] including:
     * - Zipkin\Instrumentation\Http\Server\Response the response being.
     * - mixed the delegated response.
     * - Zipkin\Instrumentation\Http\Server\Request the request originating
     *   originating the response.
     */
    abstract public static function createResponse(
        int $statusCode,
        $headers = [],
        $body = null,
        ?Request $request = null,
        string $route = null
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
        $this->assertEquals(202, $response->getStatusCode());
        $this->assertSame($request, $response->getRequest());
        $this->assertSame($delegateResponse, $response->unwrap());
    }
}
