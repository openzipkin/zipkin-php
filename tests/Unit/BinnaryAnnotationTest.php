<?php

namespace ZipkinTests;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Zipkin\BinaryAnnotation;

final class BinaryAnnotationTest extends PHPUnit_Framework_TestCase
{
    const TEST_VALUE = 'test_value';

    public function testAnnotationCreationFailsDueToInvalidValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid binary annotation value');
        BinaryAnnotation::create(new \stdClass());
    }

    public function testAnnotationCreationSuccess()
    {
        $annotation = BinaryAnnotation::create(self::TEST_VALUE);
        $this->assertEquals(self::TEST_VALUE, $annotation->getValue());
    }
}
