<?php

declare(strict_types=1);

namespace Zipkin;

use Zipkin\Recording\Span as MutableSpan;

interface Reporter
{
    /**
     * Sends the given spans to the transport.
     *
     * @param array<MutableSpan> $spans
     * @return void
     */
    public function report(array $spans): void;
}
