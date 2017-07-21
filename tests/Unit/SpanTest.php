<?php

namespace ZipkinTests\Unit;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Zipkin\Span;
use Zipkin\Timestamp;

class SpanTest extends PHPUnit_Framework_TestCase
{
    const TEST_NAME = 'test_span';
    const TEST_START_TIMESTAMP = 1472470996199000;

    public function testSpanCreationFailsDueToInvalidName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Non empty string name is expected, got \'\'');
        Span::create('');
    }

    public function testSpanCreationWithValidNameSuccess()
    {
        $span = Span::create(self::TEST_NAME);
        $this->assertEquals(self::TEST_NAME, $span->getName());
    }

    public function testSpanCreationWithInvalidTimestampFails()
    {
        $options = [
            'start_timestamp' => 100,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valid microtime expected, got \'100\'');

        Span::create(self::TEST_NAME, $options);
    }

    public function testSpanCreationWithValidTimestampSuccess()
    {
        $options = [
            'start_timestamp' => self::TEST_START_TIMESTAMP,
        ];

        $span = Span::create(self::TEST_NAME, $options);

        $this->assertEquals(self::TEST_START_TIMESTAMP, $span->getStartTimestamp());
    }

    public function testFinishASpanHasTheExpectedDuration()
    {
        $before = Timestamp\now();
        $span = Span::create(self::TEST_NAME);
        $span->finish();
        $after = Timestamp\now();

        $this->assertTrue(($after - $before) > $span->getDuration());
    }
}
