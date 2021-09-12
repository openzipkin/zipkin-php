<?php

declare(strict_types=1);

namespace Zipkin\Timestamp;

/**
 * Returns current timestamp in the zipkin format.
 */
function now(): int
{
    return (int) (\microtime(true) * 1000 * 1000);
}

/**
 * Checks whether a timestamp is valid or not.
 */
function isValid(int $timestamp): bool
{
    return \strlen((string) $timestamp) === 16;
}
