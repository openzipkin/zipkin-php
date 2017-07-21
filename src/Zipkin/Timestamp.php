<?php

namespace Zipkin\Timestamp;

function now()
{
    return (microtime(true) * 1000 * 1000);
}

function is_valid_timestamp($timestamp)
{
    return
        is_numeric($timestamp) &&
        strlen($timestamp) === 16;
}
