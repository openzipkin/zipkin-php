<?php

declare(strict_types=1);

namespace ZipkinTests\Unit\Instrumentation\Http\Server;

use Zipkin\Instrumentation\Http\Server\Response;
use Zipkin\Instrumentation\Http\Server\Request;
use PHPUnit\Framework\TestCase;

abstract class BaseResponseTestCase extends TestCase
{
    /**
     * @return mixed[] including:
     * - Zipkin\Instrumentation\Http\Server\Response the response being.
     * - mixed the delegated response.
     * - Zipkin\Instrumentation\Http\Server\Request the request originating
     *   the response.
     * - string the route
     */
    abstract public static function createResponse(
        int $statusCode,
        $headers = [],
        $body = null,
        ?Request $request = null,
        ?string $route = null
    ): array;

    /**
     * supportsRoute tells if the request implementation supports storing the
     * route or not.
     */
    protected static function supportsRoute(): bool
    {
        return false;
    }

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
        list($response, $delegateResponse, $_, $route) = static::createResponse(
            202, // status code
            [], // headers
            null, // body
            $request, // request
            '/users/{user_id}' //route
        );

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertSame($request, $response->getRequest());
        $this->assertSame($delegateResponse, $response->unwrap());
        if (self::supportsRoute()) {
            $this->assertSame('/users/{user_id}', $route);
        }
    }
}
