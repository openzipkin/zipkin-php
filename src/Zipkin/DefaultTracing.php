<?php

declare(strict_types=1);

namespace Zipkin;

use Zipkin\Propagation\B3;
use Zipkin\Propagation\CurrentTraceContext;
use Zipkin\Propagation\Propagation;
use Zipkin\Reporter;

final class DefaultTracing implements Tracing
{
    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * @var Propagation
     */
    private $propagation;

    /**
     * @var bool
     */
    private $isNoop;

    /**
     * @param Endpoint $localEndpoint
     * @param Reporter $reporter
     * @param Sampler $sampler
     * @param bool $usesTraceId128bits
     * @param CurrentTraceContext $currentTraceContext
     * @param bool $isNoop
     */
    public function __construct(
        Endpoint $localEndpoint,
        Reporter $reporter,
        Sampler $sampler,
        $usesTraceId128bits,
        CurrentTraceContext $currentTraceContext,
        bool $isNoop,
        Propagation $propagation,
        bool $supportsJoin
    ) {
        $this->tracer = new Tracer(
            $localEndpoint,
            $reporter,
            $sampler,
            $usesTraceId128bits,
            $currentTraceContext,
            $isNoop,
            $supportsJoin
        );

        $this->propagation = $propagation;
        $this->isNoop = $isNoop;
    }

    /**
     * @return Tracer
     */
    public function getTracer(): Tracer
    {
        return $this->tracer;
    }

    /**
     * @return Propagation
     */
    public function getPropagation(): Propagation
    {
        return $this->propagation;
    }

    /**
     * When true, no recording is done and nothing is reported to zipkin. However, trace context is
     * still injected into outgoing requests.
     *
     * @return bool
     * @see Span#isNoop()
     */
    public function isNoop(): bool
    {
        return $this->isNoop;
    }
}
