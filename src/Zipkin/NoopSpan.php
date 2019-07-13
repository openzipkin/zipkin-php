<?php

declare(strict_types=1);

namespace Zipkin;

use Zipkin\Propagation\TraceContext;

final class NoopSpan implements Span
{
    private $context;

    private function __construct(TraceContext $context)
    {
        $this->context = $context;
    }

    /**
     * @param TraceContext $context
     * @return NoopSpan
     */
    public static function create(TraceContext $context): self
    {
        return new self($context);
    }

    /**
     * When true, no recording is done and nothing is reported to zipkin. However, this span should
     * still be injected into outgoing requests. Use this flag to avoid performing expensive
     * computation.
     *
     * @return bool
     */
    public function isNoop(): bool
    {
        return true;
    }

    /**
     * @return TraceContext
     */
    public function getContext(): TraceContext
    {
        return $this->context;
    }

    /**
     * Starts the span with an implicit timestamp.
     *
     * Spans can be modified before calling start. For example, you can add tags to the span and
     * set its name without lock contention.
     *
     * @param int|null $timestamp
     * @return self
     */
    public function start(?int $timestamp = null): Span
    {
        return $this;
    }

    /**
     * Sets the string name for the logical operation this span represents.
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): Span
    {
        return $this;
    }

    /**
     * The kind of span is optional. When set, it affects how a span is reported. For example, if the
     * kind is {@link Zipkin\Kind\SERVER}, the span's start timestamp is implicitly annotated as "sr"
     * and that plus its duration as "ss".
     *
     * @param string $kind
     * @return self
     */
    public function setKind(string $kind): Span
    {
        return $this;
    }

    /**
     * Tags give your span context for search, viewing and analysis. For example, a key
     * "your_app.version" would let you lookup spans by version. A tag {@link Zipkin\Tags\SQL_QUERY}
     * isn't searchable, but it can help in debugging when viewing a trace.
     *
     * @param string $key Name used to lookup spans, such as "your_app.version". See {@link Zipkin\Tags} for
     * standard ones.
     * @param string $value value, cannot be <code>null</code>.
     * @return self
     */
    public function tag(string $key, string $value): Span
    {
        return $this;
    }

    /**
     * Associates an event that explains latency with the current system time.
     *
     * @param string $value A short tag indicating the event, like "finagle.retry"
     * @param int $timestamp
     * @return self
     * @see Annotations
     */
    public function annotate(string $value, int $timestamp): Span
    {
        return $this;
    }

    /**
     * For a client span, this would be the server's address.
     *
     * It is often expensive to derive a remote address: always check {@link #isNoop()} first!
     *
     * @param Endpoint $remoteEndpoint
     * @return self
     */
    public function setRemoteEndpoint(Endpoint $remoteEndpoint): Span
    {
        return $this;
    }

    /**
     * Throws away the current span without reporting it.
     *
     * @return void
     */
    public function abandon(): void
    {
    }

    /**
     * Like {@link #finish()}, except with a given timestamp in microseconds.
     *
     * {@link zipkin.Span#duration Zipkin's span duration} is derived by subtracting the start
     * timestamp from this, and set when appropriate.
     *
     * @param int|null $timestamp
     * @return void
     */
    public function finish(?int $timestamp = null): void
    {
    }

    /**
     * Reports the span, even if unfinished. Most users will not call this method.
     *
     * This primarily supports two use cases: one-way spans and orphaned spans.
     * For example, a one-way span can be modeled as a span where one tracer calls start and another
     * calls finish. In order to report that span from its origin, flush must be called.
     *
     * Another example is where a user didn't call finish within a deadline or before a shutdown
     * occurs. By flushing, you can report what was in progress.
     *
     * @return void
     */
    public function flush(): void
    {
    }
}
