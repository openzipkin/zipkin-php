<?php

declare(strict_types=1);

namespace Zipkin;

use Zipkin\Reporter;
use Zipkin\Propagation\Propagation;
use Zipkin\Propagation\CurrentTraceContext;
use Zipkin\Propagation\B3;

final class DefaultTracing implements Tracing
{
    private Tracer $tracer;

    private Propagation $propagation;

    private bool $isNoop;

    public function __construct(
        Endpoint $localEndpoint,
        Reporter $reporter,
        Sampler $sampler,
        $usesTraceId128bits,
        CurrentTraceContext $currentTraceContext,
        bool $isNoop,
        Propagation $propagation,
        bool $supportsJoin,
        bool $alwaysReportSpans = false
    ) {
        $this->tracer = new Tracer(
            $localEndpoint,
            $reporter,
            $sampler,
            $usesTraceId128bits,
            $currentTraceContext,
            $isNoop,
            $supportsJoin,
            $alwaysReportSpans
        );

        $this->propagation = $propagation;
        $this->isNoop = $isNoop;
    }

    public function getTracer(): Tracer
    {
        return $this->tracer;
    }

    public function getPropagation(): Propagation
    {
        return $this->propagation;
    }

    /**
     * When true, no recording is done and nothing is reported to zipkin. However, trace context is
     * still injected into outgoing requests.
     *
     * @see Span#isNoop()
     */
    public function isNoop(): bool
    {
        return $this->isNoop;
    }
}
