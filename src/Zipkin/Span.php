<?php

namespace Zipkin;

use Zipkin\Propagation\TraceContext;

interface Span
{
    /**
     * When true, no recording is done and nothing is reported to zipkin. However, this span should
     * still be injected into outgoing requests. Use this flag to avoid performing expensive
     * computation.
     *
     * @return bool
     */
    public function isNoop();

    /**
     * @return TraceContext
     */
    public function getContext();

    /**
     * Starts the span with an implicit timestamp.
     *
     * Spans can be modified before calling start. For example, you can add tags to the span and
     * set its name without lock contention.
     *
     * @param int|null $timestamp
     * @return void
     */
    public function start($timestamp = null);

    /**
     * Sets the string name for the logical operation this span represents.
     *
     * @param string $name
     * @return void
     */
    public function setName($name);

    /**
     * The kind of span is optional. When set, it affects how a span is reported. For example, if the
     * kind is {@link Zipkin\Kind\SERVER}, the span's start timestamp is implicitly annotated as "sr"
     * and that plus its duration as "ss".
     *
     * The value must be strictly one of the ones listed in {@link Kind}.
     *
     * @param string $kind
     * @return void
     */
    public function setKind($kind);

    /**
     * Tags give your span context for search, viewing and analysis. For example, a key
     * "your_app.version" would let you lookup spans by version. A tag {@link Zipkin\Tags\SQL_QUERY}
     * isn't searchable, but it can help in debugging when viewing a trace.
     *
     * @param string $key Name used to lookup spans, such as "your_app.version". See {@link Zipkin\Tags} for
     * standard ones.
     * @param $value String value, cannot be <code>null</code>.
     * @return void
     */
    public function tag($key, $value);

    /**
     * Associates an event that explains latency with the current system time.
     *
     * @param string $value A short tag indicating the event, like "finagle.retry"
     * @param int $timestamp
     * @return void
     * @see Annotations
     */
    public function annotate($value, $timestamp);

    /**
     * For a client span, this would be the server's address.
     *
     * It is often expensive to derive a remote address: always check {@link #isNoop()} first!
     *
     * @param Endpoint $remoteEndpoint
     * @return void
     */
    public function setRemoteEndpoint(Endpoint $remoteEndpoint);

    /**
     * Throws away the current span without reporting it.
     *
     * @return void
     */
    public function abandon();

    /**
     * Like {@link #finish()}, except with a given timestamp in microseconds.
     *
     * {@link zipkin.Span#duration Zipkin's span duration} is derived by subtracting the start
     * timestamp from this, and set when appropriate.
     *
     * @param int|null $timestamp
     * @return void
     */
    public function finish($timestamp = null);

    /**
     * Reports the span, even if unfinished. Most users will not call this method.
     *
     * This primarily supports two use cases: one-way spans and orphaned spans.
     * For example, a one-way span can be modeled as a span where one tracer calls start and another
     * calls finish. In order to report that span from its origin, flush must be called.
     *
     * Another example is where a user did not call finish within a deadline or before a shutdown
     * occurs. By flushing, you can report what was in progress.
     *
     * @return void
     */
    public function flush();
}
