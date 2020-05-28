<?php

declare(strict_types=1);

namespace Zipkin\Propagation\Exceptions;

use InvalidArgumentException;

final class InvalidTraceContextArgument extends InvalidArgumentException
{
    public static function forTraceId(string $traceId): self
    {
        return new self(\sprintf('Invalid trace id, got %s', $traceId));
    }

    public static function forSpanId(string $spanId): self
    {
        return new self(\sprintf('Invalid span id, got %s', $spanId));
    }

    public static function forParentSpanId(string $parentId): self
    {
        return new self(\sprintf('Invalid parent span id, got %s', $parentId));
    }

    public static function forSampling(string $value): self
    {
        return new self(\sprintf('Invalid sampling bit, got %s, expected 1, 0 or d', $value));
    }
}
