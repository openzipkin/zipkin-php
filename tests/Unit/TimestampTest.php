<?php

namespace ZipkinTests\Unit;

use Zipkin\Timestamp;

final class TimestampTest extends \PHPUnit_Framework_TestCase
{
    public function testNowHasTheExpectedLength()
    {
        $now = Timestamp\now();
        $this->assertEquals(16, strlen((string) $now));
    }

    /**
     * @dataProvider timestampProvider
     */
    public function testIsValidHasProducesTheExpectedOutput($timestamp, $isValid)
    {
        $this->assertEquals($isValid, Timestamp\isValid($timestamp));
    }

    public function timestampProvider()
    {
        return [
            [Timestamp\now(), true],
            [1234567890123456, true],
            [123456789012345, false],
            ['1234567890123456', false],
            ['123456789012345a', false],
            ['abcdefgh12345678', false]
        ];
    }
}
