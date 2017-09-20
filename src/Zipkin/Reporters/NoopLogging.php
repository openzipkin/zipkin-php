<?php

namespace Zipkin\Reporters;

use Zipkin\Recording\Span;
use Zipkin\Reporter;

final class NoopLogging implements Reporter
{
    /**
     * @param Span[] $spans
     * @return void
     */
    public function report(array $spans)
    {
    }
}