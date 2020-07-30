<?php

declare(strict_types=1);

namespace ZipkinTests\Unit\Instrumentation\Http\Client;

use PHPUnit\Framework\TestCase;

abstract class BaseRequestTest extends TestCase
{
    abstract public static function createRequest(
        string $method,
        string $uri,
        $headers = [],
        $body = null
    ): array;

    public function testRequestIsCreatedSuccessfully()
    {
        list($request, $delegateRequest) = static::createRequest(
            'GET',
            'http://test.com/path',
            ['test_key' => 'test_value']
        );
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/path', $request->getPath());
        $this->assertNull($request->getHeader('test_missing_key'));
        $this->assertEquals('test_value', $request->getHeader('test_key'));
        $this->assertSame($delegateRequest, $request->unwrap());
    }

    /**
     * @dataProvider rootPathsProvider
     */
    public function testRequestIsNormalizesRootPath(string $path)
    {
        list($request) = static::createRequest('GET', $path, ['test_key' => 'test_value']);
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
