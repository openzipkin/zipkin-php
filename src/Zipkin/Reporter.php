<?php

namespace Zipkin;

use Zipkin\Recording\Span;

interface Reporter
{
    /**
     * @param Span[]|\Generator $spans
     * @return void
     */
    public function report($spans);
}