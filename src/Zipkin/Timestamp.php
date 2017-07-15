<?php

namespace Zipkin\Timestamp;

function now()
{
    return microtime(true);
}

function is_valid_timestamp($timestamp)
{
    return preg_match('/\d{10}\.\d{4}/', (string) $timestamp) === 1;
}
