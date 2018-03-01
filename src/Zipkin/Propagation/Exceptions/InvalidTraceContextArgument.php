<?php

namespace Zipkin\Propagation\Exceptions;

use InvalidArgumentException;

final class InvalidTraceContextArgument extends InvalidArgumentException
{
    public static function forTraceId($traceId)
    {
        return new self(sprintf('Invalid trace id, got %s', $traceId));
    }

    public static function forSpanId($spanId)
    {
        return new self(sprintf('Invalid span id, got %s', $spanId));
    }

    public static function forParentSpanId($parentId)
    {
        return new self(sprintf('Invalid parent span id, got %s', $parentId));
    }

    public static function forSampled($isSampled)
    {
        return new self(sprintf('is Sampled should be boolean or null, got %s', gettype($isSampled)));
    }

    public static function forDebug($isDebug)
    {
        return new self(sprintf('isDebug should be boolean, got %s', gettype($isDebug)));
    }
}
