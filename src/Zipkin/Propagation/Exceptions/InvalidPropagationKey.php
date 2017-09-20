<?php

namespace Zipkin\Propagation\Exceptions;

use InvalidArgumentException;

final class InvalidPropagationKey extends InvalidArgumentException
{
    public static function forEmptyKey()
    {
        return new self('Empty key');
    }

    public static function forInvalidKey($key)
    {
        return new self(sprintf('Invalid key. Expected string, got %s', gettype($key)));
    }
}
