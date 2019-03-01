<?php

declare(strict_types=1);

namespace Zipkin\Propagation\Id;

/**
 * @return string
 */
function generateTraceIdWith128bits(): string
{
    return bin2hex(openssl_random_pseudo_bytes(16));
}

/**
 * @return string
 */
function generateNextId(): string
{
    return bin2hex(openssl_random_pseudo_bytes(8));
}

/**
 * @param string $value
 * @return bool
 */
function isValidTraceId(string $value): bool
{
    return ctype_xdigit($value) &&
        strlen($value) > 0 && strlen($value) <= 32;
}

/**
 * @param string $value
 * @return bool
 */
function isValidSpanId(string $value): bool
{
    return ctype_xdigit($value) &&
        strlen($value) > 0 && strlen($value) <= 16;
}
