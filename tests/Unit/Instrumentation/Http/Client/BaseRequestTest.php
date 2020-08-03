<?php

declare(strict_types=1);

namespace ZipkinTests\Unit\Instrumentation\Http\Client;

use Zipkin\Instrumentation\Http\Client\Request;
use PHPUnit\Framework\TestCase;

abstract class BaseRequestTest extends TestCase
{
    /**
     * @return mixed[] including
     * - Request the request
     * - mixed the delegate request
     */
    abstract public static function createRequest(
        string $method,
        string $uri,
        $headers = [],
        $body = null
    ): array;

    public function testRequestIsCreatedSuccessfully(): void
    {
        list($request, $delegateRequest) = static::createRequest(
            'GET',
            'http://test.com/path/to',
            ['test_key' => 'test_value']
        );
        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/path/to', $request->getPath());
        $this->assertNull($request->getHeader('test_missing_key'));
        $this->assertEquals('test_value', $request->getHeader('test_key'));
        $this->assertSame($delegateRequest, $request->unwrap());
    }

    /**
     * @dataProvider rootPathsProvider
     */
    public function testRequestIsNormalizesRootPath(string $path): void
    {
        list($request) = static::createRequest('GET', $path, ['test_key' => 'test_value']);
        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('/', $request->getPath());
    }

    public static function rootPathsProvider(): array
    {
        return [
            ['http://test.com'],
            ['http://test.com?'],
            ['http://test.com/'],
        ];
    }
}
