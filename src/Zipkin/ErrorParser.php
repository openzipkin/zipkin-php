<?php

declare(strict_types=1);

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
    /**
     * Parses the error into a set of tags.
     *
     * @param Throwable $e
     * @return array<string,string>
     */
    public function parseTags(Throwable $e): array;
}
