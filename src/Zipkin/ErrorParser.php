<?php

namespace Zipkin;

use Zipkin\Recording\Span;
use Throwable;

interface ErrorParser
{
    public function parseTags(Throwable $e): array;
    public function parseAnnotations(Throwable $e): array;
}
