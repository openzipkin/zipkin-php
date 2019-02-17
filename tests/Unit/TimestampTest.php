<?php

namespace ZipkinTests\Unit;

use PHPUnit\Framework\TestCase;
use function Zipkin\Timestamp\now;
use function Zipkin\Timestamp\isValid;

final class TimestampTest extends TestCase
{
    public function testNowHasTheExpectedLength()
    {
        $now = now();
        $this->assertEquals(16, strlen((string) $now));
    }

    /**
     * @dataProvider timestampProvider
     */
    public function testIsValidProducesTheExpectedOutput($timestamp, $isValid)
    {
        $this->assertEquals($isValid, isValid($timestamp));
    }

    public function timestampProvider()
    {
        return [
            [now(), true],
            [-1, false],
            [1234567890123456, true],
            [123456789012345, false],
        ];
    }
}
