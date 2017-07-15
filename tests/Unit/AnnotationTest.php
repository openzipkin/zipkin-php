<?php

namespace ZipkinTests;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Zipkin\Annotation;

final class AnnotationTest extends PHPUnit_Framework_TestCase
{
    const TEST_VALUE = 'test_value';
    const TEST_TIMESTAMP = 1500125039.5501;

    public function testAnAnnotationCreationSuccess()
    {
        $annotation = Annotation::create(self::TEST_VALUE, self::TEST_TIMESTAMP);
        $this->assertEquals(self::TEST_VALUE, $annotation->getValue());
        $this->assertEquals(self::TEST_TIMESTAMP, $annotation->getTimestamp());
    }

    public function testAnnotationCreationFailsOnNonFloatTimestamp()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valid timestamp represented microtime expected, got \'100\'');
        Annotation::create(self::TEST_VALUE, 100);
    }
}
