<?php

namespace Zipkin;

use Zipkin\Recording\Span;
use Throwable;

/**
 * ErrorParser turns a Throwable into a map of tags.
 * By default it turns into an error with the Throwable's
 * message.
 */
interface ErrorParser
{
    public function parseTags(Throwable $e): array;
}
