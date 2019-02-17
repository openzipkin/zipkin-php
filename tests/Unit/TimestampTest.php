<?php

namespace ZipkinTests\Unit;

use PHPUnit\Framework\TestCase;
use Zipkin\Timestamp;

final class TimestampTest extends TestCase
{
    public function testNowHasTheExpectedLength()
    {
        $now = Timestamp\now();
        $this->assertEquals(16, strlen((string) $now));
    }

    /**
     * @dataProvider timestampProvider
     */
    public function testIsValidProducesTheExpectedOutput($timestamp, $isValid)
    {
        $this->assertEquals($isValid, Timestamp\isValid($timestamp));
    }

    public function timestampProvider()
    {
        return [
            [Timestamp\now(), true],
            [-1, false],
            [1234567890123456, true],
            [123456789012345, false],
        ];
    }
}
