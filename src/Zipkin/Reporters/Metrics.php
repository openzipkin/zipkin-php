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
 * <p>The following relationships can be used to consider health of the tracing system.
 * <pre>
 * <ul>
 * <li>{@link #updateQueuedSpans Pending spans}. Alert when this increases over time as it could
 * lead to dropped spans.
 * <li>Dropped spans = Alert when this increases as it could indicate a queue backup.
 * <li>Successful Messages = {@link #incrementMessages() Accepted messages} -
 * {@link #incrementMessagesDropped Dropped messages}. Alert when this is more than amount of
 * messages received from collectors.</li>
 * </li>
 * </ul>
 * </pre>
 */
interface Metrics
{
    /**
     * Increments count of message attempts, which contain 1 or more spans. Ex POST requests or Kafka
     * messages sent.
     */
    public function incrementMessages();

    /**
     * Increments count of messages that could not be sent. Ex host unavailable, or peer disconnect.
     * @param \Throwable|\Exception $cause
     * @return void
     */
    public function incrementMessagesDropped($cause);

    /**
     * Increments the count of spans reported. When {@link AsyncReporter} is used, reported spans will
     * usually be a larger number than messages.
     *
     * @param int $quantity
     * @return void
     */
    public function incrementSpans($quantity);

    /**
     * Increments the number of encoded span bytes reported.
     * @param int $quantity
     * @return void
     */
    public function incrementSpanBytes($quantity);

    /**
     * Increments the number of bytes containing encoded spans in a message.
     *
     * <p>This is a function of span bytes per message and overhead</p>
     *
     * @see Sender#messageSizeInBytes
     * @param $quantity
     * @return void
     */
    public function incrementMessageBytes($quantity);

    /**
     * Increments the count of spans dropped for any reason. For example, failure queueing or
     * sending.
     *
     * @param int $quantity
     * @return void
     */
    public function incrementSpansDropped($quantity);

    /**
     * Updates the count of spans pending, following a flush activity.
     *
     * @param int $update
     * @return void
     */
    public function updateQueuedSpans($update);

    /**
     * Updates the count of encoded span bytes pending, following a flush activity.
     *
     * @param int $update
     * @return void
     */
    public function updateQueuedBytes($update);
}
