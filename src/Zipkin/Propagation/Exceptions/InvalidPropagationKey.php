<?php

declare(strict_types=1);

namespace Zipkin\Propagation\Exceptions;

use InvalidArgumentException;

final class InvalidPropagationKey extends InvalidArgumentException
{
    public static function forEmptyKey(): self
    {
        return new self('Empty key');
    }
}
