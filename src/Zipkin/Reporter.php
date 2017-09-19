<?php

namespace Zipkin;

use Zipkin\Recording\Span;

interface Reporter
{
    /**
     * @param Span[] $spans
     * @return void
     */
    public function report(array $spans);
}