<?php

namespace Zipkin\Timestamp;

function now()
{
    return (int) (microtime(true) * 1000 * 1000);
}

function is_valid_timestamp($timestamp)
{
    return ctype_digit((string) $timestamp) && strlen((string) $timestamp) === 16;
}
