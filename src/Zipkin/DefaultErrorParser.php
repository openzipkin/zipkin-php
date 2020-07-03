<?php

namespace Zipkin;

use Zipkin\Tags;
use Throwable;

class DefaultErrorParser implements ErrorParser
{
    public function parseTags(Throwable $e): array
    {
        return [Tags\ERROR => $e->getMessage()];
    }

    public function parseAnnotations(Throwable $e): array
    {
        return [];
    }
}
