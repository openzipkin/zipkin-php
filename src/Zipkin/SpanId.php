<?php

namespace Zipkin\Kind;

use InvalidArgumentException;

final class SpanId
{
    /**
     * @var int
     */
    private $value;

    private function __construct($value)
    {
        $this->value = $value;
    }

    public static function fromString($value)
    {
        self::validateValue($value);

        return new self($value);
    }

    public static function randomId()
    {
        return new self(self::generateValue());
    }

    private static function validateValue($value)
    {
        if (!(ctype_xdigit((string) $value) && strlen((string) $value) === 16)) {
            throw new InvalidArgumentException('Invalid span ID value.');
        }
    }


    /**
     * Generates 128-bit hex-encoded identifier
     * http://zipkin.io/pages/instrumenting.html#trace-identifiers
     *
     * @return string
     */
    private static function generateValue()
    {
        return bin2hex(openssl_random_pseudo_bytes(8));
    }

    public function getValue()
    {
        return $this->value;
    }
}