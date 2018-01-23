<?php

namespace Zipkin;

/**
 * Sampler is responsible for deciding if a particular trace should be "sampled", i.e. whether the
 * overhead of tracing will occur and/or if a trace will be reported to the collection tier.
 *
 * Zipkin v1 uses before-the-fact sampling. This means that the decision to keep or drop the
 * trace is made before any work is measured, or annotations are added. As such, the input parameter
 * to zipkin v1 samplers is the trace ID (lower 64-bits under the assumption all bits are random).
 *
 * The instrumentation sampling decision happens once, at the root of the trace, and is
 * propagated downstream. For this reason, the algorithm needn't be consistent based on trace ID.
 */
interface Sampler
{
    /**
     * Returns true if the trace ID should be measured.
     *
     * @param string $traceId
     * @return bool
     */
    public function isSampled($traceId);
}
