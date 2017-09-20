<?php

namespace Zipkin;

use Zipkin\Recording\Span as MutableSpan;

interface Reporter
{
    /**
     * @param MutableSpan[] $spans
     * @return void
     */
    public function report(array $spans);
}
