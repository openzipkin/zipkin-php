<?php

declare(strict_types=1);

namespace Zipkin\Propagation\Id;

/**
 * @return string
 * @throws \RuntimeException
 */
function generateTraceIdWith128bits(): string
{
    $pseudoBytes = \openssl_random_pseudo_bytes(16);

    if ($pseudoBytes === false) {
        throw new \RuntimeException("Unable to generate a pseudo-random byte string.");
    }

    return \bin2hex($pseudoBytes);
}

/**
 * @return string
 * @throws \RuntimeException
 */
function generateNextId(): string
{
    $pseudoBytes = \openssl_random_pseudo_bytes(16);

    if ($pseudoBytes === false) {
        throw new \RuntimeException("Unable to generate a pseudo-random byte string.");
    }

    return \bin2hex(\openssl_random_pseudo_bytes(8));
}

/**
 * @param string $value
 * @return bool
 */
function isValidTraceId(string $value): bool
{
    return \ctype_xdigit($value) &&
        \strlen($value) > 0 && \strlen($value) <= 32;
}

/**
 * @param string $value
 * @return bool
 */
function isValidSpanId(string $value): bool
{
    return \ctype_xdigit($value) &&
        \strlen($value) > 0 && \strlen($value) <= 16;
}
