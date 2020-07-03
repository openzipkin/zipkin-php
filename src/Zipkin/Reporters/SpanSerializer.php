<?php

declare(strict_types=1);

namespace Zipkin\Reporters;

use Zipkin\Recording\ReadbackSpan;

/**
 * SpanSerializer turns a list of spans into a series of
 * bytes (byte = character).
 */
interface SpanSerializer
{
    /**
     * @param ReadbackSpan[]|array $spans
     */
    public function serialize(array $spans): string;
}
