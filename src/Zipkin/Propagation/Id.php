<?php

namespace Zipkin\Propagation\Id;

/**
 * @return string
 */
function generateTraceIdWith128bits()
{
    return bin2hex(openssl_random_pseudo_bytes(16));
}

/**
 * @return string
 */
function generateNextId()
{
    return bin2hex(openssl_random_pseudo_bytes(8));
}

/**
 * @param string $value
 * @return bool
 */
function isValidTraceId($value)
{
    return ctype_xdigit((string) $value) &&
        strlen((string) $value) > 0 && strlen((string) $value) <= 32;
}

/**
 * @param string $value
 * @return bool
 */
function isValidSpanId($value)
{
    return ctype_xdigit((string) $value) &&
        strlen((string) $value) > 0 && strlen((string) $value) <= 16;
}
