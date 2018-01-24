<?php

namespace ZipkinTests\Unit\Propagation;

use PHPUnit_Framework_TestCase;
use Zipkin\Propagation\Id;

final class IdTest extends PHPUnit_Framework_TestCase
{
    public function testNextIdSuccess()
    {
        $nextId = Id\generateNextId();
        $this->assertTrue(ctype_xdigit($nextId));
        $this->assertEquals(16, strlen($nextId));
    }

    public function testTraceIdWith128bitsSuccess()
    {
        $nextId = Id\generateTraceIdWith128bits();
        $this->assertTrue(ctype_xdigit($nextId));
        $this->assertEquals(32, strlen($nextId));
    }

    /**
     * @dataProvider spanIdsDataProvider
     */
    public function testIsValidSpanIdSuccess($spanId, $isValid)
    {
        $this->assertEquals($isValid, Id\isValidSpanId($spanId));
    }

    public function spanIdsDataProvider()
    {
        return [
            ['', false],
            ['1', true],
            ['50d1e105a060618', true],
            ['050d1e105a060618', true],
            ['g50d1e105a060618', false],
            ['050d1e105a060618a', false],
        ];
    }

    /**
     * @dataProvider traceIdsDataProvider
     */
    public function testIsValidTraceIdSuccess($traceId, $isValid)
    {
        $this->assertEquals($isValid, Id\isValidTraceId($traceId));
    }

    public function traceIdsDataProvider()
    {
        return [
            ['', false],
            ['1', true],
            ['050d1e105a060618', true],
            ['g50d1e105a060618', false],
            ['050d1e105a060618050d1e105a060618', true],
            ['050d1e105a060618g50d1e105a060618', false],
            ['050d1e105a060618050d1e105a060618a', false],
        ];
    }
}
