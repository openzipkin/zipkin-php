<?php

declare(strict_types=1);

namespace ZipkinTests\Unit\Instrumentation\Http\Server\Psr15;

use Zipkin\Instrumentation\Http\Server\Psr15\Request;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Request as Psr7Request;

final class RequestTest extends TestCase
{
    public function testRequestIsCreatedSuccessfully()
    {
        $delegateRequest = new Psr7Request('GET', 'http://test.com/path', ['test_key' => 'test_value']);
        $request = new Request($delegateRequest);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/path', $request->getPath());
        $this->assertNull($request->getHeader('test_missing_key'));
        $this->assertEquals('test_value', $request->getHeader('test_key'));
        $this->assertSame($delegateRequest, $request->unwrap());
    }

    /**
     * @dataProvider emptyPaths
     */
    public function testRequestIsNormalizesEmptyPath(string $path)
    {
        $delegateRequest = new Psr7Request('GET', 'http://test.com', ['test_key' => 'test_value']);
        $request = new Request($delegateRequest);
        $this->assertEquals('/', $request->getPath());
    }

    public function emptyPaths(): array
    {
        return [
            ['http://test.com'],
            ['http://test.com/'],
        ];
    }
}
