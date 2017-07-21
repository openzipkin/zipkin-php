<?php

namespace ZipkinTests;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Zipkin\Annotation;

final class AnnotationTest extends PHPUnit_Framework_TestCase
{
    const TEST_VALUE = 'test_value';
    const TEST_TIMESTAMP = 1500125039550100;

    public function testAnnotationCreationFailsDueToInvalidValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid annotation value');
        Annotation::create(new \stdClass(), self::TEST_TIMESTAMP);
    }

    public function testAnnotationCreationFailsOnNonFloatTimestamp()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valid timestamp represented microtime expected, got \'100\'');
        Annotation::create(self::TEST_VALUE, 100);
    }

    public function testAnnotationCreationSuccess()
    {
        $annotation = Annotation::create(self::TEST_VALUE, self::TEST_TIMESTAMP);
        $this->assertEquals(self::TEST_VALUE, $annotation->getValue());
        $this->assertEquals(self::TEST_TIMESTAMP, $annotation->getTimestamp());
    }
}
