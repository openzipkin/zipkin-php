<?php

declare(strict_types=1);

namespace Zipkin\Reporters;

use Zipkin\Recording\ReadbackSpan;

interface SpanSerializer
{
    /**
     * @param ReadbackSpan[]|array $spans
     */
    public function serialize(array $spans): string;
}
