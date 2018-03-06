<?php

namespace Zipkin\Reporters;

/**
 * Instrumented applications report spans over a transport such as Kafka to Zipkin Collectors.
 *
 * <p>Callbacks on this type are invoked by zipkin reporters to improve the visibility of the
 * system. A typical implementation will report metrics to a telemetry system for analysis and
 * reporting.</p>
 *
 * <h3>Spans Reported vs Queryable Spans</h3>
 *
 * <p>A span in the context of reporting is <= span in the context of query. Instrumentation should
 * report a span only once except, but certain types of spans cross the network. For example, RPC
 * spans are reported at the client and the server separately.</p>
 *
 * <h3>Key Relationships</h3>
 *
 * <p>The following relationships can be used to consider health of the tracing system.</p>
 * <ul>
 * <li>Dropped spans = Alert when this increases as it could indicate a queue backup.
 * </li>
 * </ul>
 */
interface Metrics
{
    /**
     * Increments the count of spans reported. When {@link AsyncReporter} is used, reported spans will
     * usually be a larger number than messages.
     *
     * @param int $quantity
     * @return void
     */
    public function incrementSpans($quantity);

    /**
     * Increments the count of spans dropped for any reason. For example, failure queueing or
     * sending.
     *
     * @param int $quantity
     * @return void
     */
    public function incrementSpansDropped($quantity);
}
