<?php

namespace Zipkin\Propagation\Exceptions;

use InvalidArgumentException;

final class InvalidPropagationCarrier extends InvalidArgumentException
{
    public static function forCarrier($carrier)
    {
        return new self(sprintf(
            'Invalid carrier. Expected array or ArrayAccess, got %s',
            is_object($carrier) ? get_class($carrier) : gettype($carrier)
        ));
    }
}