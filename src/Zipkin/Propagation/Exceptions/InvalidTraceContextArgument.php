<?php

declare(strict_types=1);

namespace Zipkin\Propagation\Exceptions;

use InvalidArgumentException;

final class InvalidTraceContextArgument extends InvalidArgumentException
{
    public static function forTraceId($traceId): self
    {
        return new self(sprintf('Invalid trace id, got %s', $traceId));
    }

    public static function forSpanId($spanId): self
    {
        return new self(sprintf('Invalid span id, got %s', $spanId));
    }

    public static function forParentSpanId($parentId): self
    {
        return new self(sprintf('Invalid parent span id, got %s', $parentId));
    }
}
