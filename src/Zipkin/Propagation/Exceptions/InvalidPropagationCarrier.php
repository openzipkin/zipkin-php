<?php

declare(strict_types=1);

namespace Zipkin\Propagation\Exceptions;

use InvalidArgumentException;

final class InvalidPropagationCarrier extends InvalidArgumentException
{
    public static function forCarrier($carrier): self
    {
        return new self(sprintf(
            'Invalid carrier of type %s',
            is_object($carrier) ? get_class($carrier) : gettype($carrier)
        ));
    }
}
