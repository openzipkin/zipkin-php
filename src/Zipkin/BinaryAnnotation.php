<?php

namespace Zipkin;

use InvalidArgumentException;

final class BinaryAnnotation
{
    private function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @param mixed $value
     * @return BinaryAnnotation
     */
    public static function create($value)
    {
        if (!is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
            throw new InvalidArgumentException('Invalid binary annotation value.');
        }

        return new self($value);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
