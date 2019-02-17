<?php

namespace Zipkin\Propagation\Exceptions;

use InvalidArgumentException;

final class InvalidPropagationKey extends InvalidArgumentException
{
    public static function forEmptyKey()
    {
        return new self('Empty key');
    }
}
