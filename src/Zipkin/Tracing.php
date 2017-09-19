<?php

namespace Zipkin;

use Zipkin\Propagation\Propagation;

interface Tracing
{
    /**
     * All tracing commands start with a {@link Span}. Use a tracer to create spans.
     *
     * @return Tracer
     */
    public function getTracer();

    /**
     * When a trace leaves the process, it needs to be propagated, usually via headers. This utility
     * is used to inject or extract a trace context from remote requests.
     *
     * @return Propagation
     */
    public function getPropagation();

    /**
     * When true, no recording is done and nothing is reported to zipkin. However, trace context is
     * still injected into outgoing requests.
     *
     * @return bool
     * @see Span#isNoop()
     */
    public function isNoop();
}
