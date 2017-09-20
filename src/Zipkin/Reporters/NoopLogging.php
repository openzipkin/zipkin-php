<?php

namespace Zipkin\Reporters;

use Zipkin\Recording\Span;
use Zipkin\Reporter;

final class NoopLogging implements Reporter
{
    /**
     * @param Span[]|\Generator $spans
     * @return void
     */
    public function report($spans)
    {
        foreach($spans as $span)
        {
            print_r($span->toArray());
        }
    }
}