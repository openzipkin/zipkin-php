<?php

namespace ZipkinTests\Unit\Propagation;

use GuzzleHttp\Psr7\Request;
use PHPUnit_Framework_TestCase;
use Zipkin\Propagation\RequestHeaders;

final class RequestHeadersTest extends PHPUnit_Framework_TestCase
{
    const TEST_KEY = 'key';
    const TEST_VALUE = 'value';

    public function testGetReturnsNullIfKeyDoesNotExists()
    {
        $request = new Request('GET', '/');
        $requestHeaders = new RequestHeaders;
        $value = $requestHeaders->get($request, self::TEST_KEY);
        $this->assertNull($value);
    }

    public function testGetReturnsTheExpectedValue()
    {
        $request = new Request('GET', '/', [self::TEST_KEY => self::TEST_VALUE]);
        $requestHeaders = new RequestHeaders;
        $value = $requestHeaders->get($request, self::TEST_KEY);
        $this->assertEquals(self::TEST_VALUE, $value);
    }

    public function testPutWritesTheExpectedValue()
    {
        $request = new Request('GET', '/');
        $requestHeaders = new RequestHeaders;
        $requestHeaders->put($request, self::TEST_KEY, self::TEST_VALUE);
        $value = $requestHeaders->get($request, self::TEST_KEY);
        $this->assertEquals(self::TEST_VALUE, $value);
    }

    public function testPutOverridesWithTheExpectedValue()
    {
        $request = new Request('GET', '/', [self::TEST_KEY => 'foobar']);
        $requestHeaders = new RequestHeaders();
        $requestHeaders->put($request, self::TEST_KEY, self::TEST_VALUE);
        $value = $requestHeaders->get($request, self::TEST_KEY);
        $this->assertEquals(self::TEST_VALUE, $value);
    }
}
