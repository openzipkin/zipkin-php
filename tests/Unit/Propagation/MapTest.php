<?php

namespace ZipkinTests\Unit\Propagation;

use ArrayObject;
use PHPUnit_Framework_TestCase;
use stdClass;
use Zipkin\Propagation\Exceptions\InvalidPropagationCarrier;
use Zipkin\Propagation\Exceptions\InvalidPropagationKey;
use Zipkin\Propagation\Map;

final class MapTest extends PHPUnit_Framework_TestCase
{
    const TEST_KEY = 'test_key';
    const TEST_KEY_INSENSITIVE = 'tEsT_KEy';
    const TEST_VALUE = 'test_value';
    const TEST_INVALID_KEY = 1;
    const TEST_EMPTY_KEY = '';

    public function testGetFromMapFailsDueToUnsupportedCarrier()
    {
        $carrier = new stdClass();
        $map = new Map();
        $this->expectException(InvalidPropagationCarrier::class);
        $map->get($carrier, self::TEST_KEY);
    }

    public function testGetFromMapSuccessForArrayAccessCarrier()
    {
        $carrier = new ArrayObject([self::TEST_KEY => self::TEST_VALUE]);
        $map = new Map();
        $value = $map->get($carrier, self::TEST_KEY);
        $this->assertEquals(self::TEST_VALUE, $value);
    }

    public function testGetFromMapCaseInsensitiveSuccess()
    {
        $carrier = [self::TEST_KEY_INSENSITIVE => self::TEST_VALUE];
        $map = new Map();
        $value = $map->get($carrier, self::TEST_KEY);
        $this->assertEquals(self::TEST_VALUE, $value);
    }

    public function testGetFromMapCaseInsensitiveReturnsNull()
    {
        $carrier = new ArrayObject([self::TEST_KEY_INSENSITIVE => self::TEST_VALUE]);
        $map = new Map();
        $value = $map->get($carrier, self::TEST_KEY);
        $this->assertNull($value);
    }

    public function testPutToMapFailsDueToInvalidKey()
    {
        $carrier = new ArrayObject();
        $map = new Map();
        $this->expectException(InvalidPropagationKey::class);
        $map->put($carrier, self::TEST_INVALID_KEY, self::TEST_VALUE);
    }

    public function testPutToMapFailsDueToEmptyKey()
    {
        $carrier = new ArrayObject();
        $map = new Map();
        $this->expectException(InvalidPropagationKey::class);
        $map->put($carrier, self::TEST_EMPTY_KEY, self::TEST_VALUE);
    }

    public function testPutToMapFailsDueToInvalidCarrier()
    {
        $carrier = new stdClass();
        $map = new Map();
        $this->expectException(InvalidPropagationCarrier::class);
        $map->put($carrier, self::TEST_KEY, self::TEST_VALUE);
    }

    public function testPutToMapSuccess()
    {
        $carrier = new ArrayObject([self::TEST_KEY => self::TEST_VALUE]);
        $map = new Map();
        $map->put($carrier, self::TEST_KEY, self::TEST_VALUE);
        $value = $map->get($carrier, self::TEST_KEY);
        $this->assertEquals(self::TEST_VALUE, $value);
    }
}
