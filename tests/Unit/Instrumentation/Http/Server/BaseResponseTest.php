<?php

declare(strict_types=1);

namespace ZipkinTests\Unit\Instrumentation\Http\Server;

use PHPUnit\Framework\TestCase;

abstract class BaseResponseTest extends TestCase
{
    /**
     * @return array including the response, delegated response and request.
     */
    abstract public static function createResponse(
        int $statusCode,
        $headers = [],
        $body = null,
        $request = null
    ): array;

    abstract public static function delegateRequestsProvider(): array;

    /**
     * @dataProvider delegateRequestsProvider
     */
    public function testResponseIsCreatedSuccessfully($request)
    {
        list($response, $delegateResponse) = static::createResponse(202, [], null, $request);
        $this->assertEquals(202, $response->getStatusCode());
        $this->assertSame($request, $response->getRequest());
        $this->assertSame($delegateResponse, $response->unwrap());
    }
}
