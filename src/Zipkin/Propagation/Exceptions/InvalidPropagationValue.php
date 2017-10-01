<?php

namespace Zipkin\Propagation\Exceptions;

use InvalidArgumentException;

final class InvalidPropagationValue extends InvalidArgumentException
{
    public static function forInvalidValue($key, $value)
    {
        return new self(sprintf(
            'Invalid value for key %s. Expected string, got %s',
            $key,
            gettype($value)
        ));
    }
}
