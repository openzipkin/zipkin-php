<?php

namespace Zipkin;

use InvalidArgumentException;
use Zipkin\Propagation\TraceContext;
use Zipkin\Timestamp;

final class RealSpan implements Span
{
    /**
     * @var Recorder
     */
    private $recorder;

    /**
     * @var TraceContext
     */
    private $traceContext;

    private function __construct(TraceContext $context, Recorder $recorder)
    {
        $this->traceContext = $context;
        $this->recorder = $recorder;
    }

    /**
     * @param TraceContext $context
     * @param Recorder $recorder
     * @return RealSpan
     */
    public static function create(TraceContext $context, Recorder $recorder)
    {
        return new self($context, $recorder);
    }

    /**
     * When true, no recording is done and nothing is reported to zipkin. However, this span should
     * still be injected into outgoing requests. Use this flag to avoid performing expensive
     * computation.
     *
     * @return bool
     */
    public function isNoop()
    {
        return false;
    }

    /**
     * @return TraceContext
     */
    public function getContext()
    {
        return $this->traceContext;
    }

    /**
     * Starts the span with an implicit timestamp.
     *
     * Spans can be modified before calling start. For example, you can add tags to the span and
     * set its name without lock contention.
     *
     * @param int $timestamp
     * @return void
     * @throws \InvalidArgumentException
     */
    public function start($timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = Timestamp\now();
        } else {
            if (!Timestamp\isValid($timestamp)) {
                throw new InvalidArgumentException(
                    sprintf('Invalid timestamp. Expected int, got %s', $timestamp)
                );
            }
        }

        $this->recorder->start($this->traceContext, $timestamp);
    }

    /**
     * Sets the string name for the logical operation this span represents.
     *
     * @param string $name
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setName($name)
    {
        if ($name !== (string) $name) {
            throw new InvalidArgumentException(
                sprintf('Invalid name. expected string, got %s', gettype($name))
            );
        }

        $this->recorder->setName($this->traceContext, $name);
    }

    /**
     * The kind of span is optional. When set, it affects how a span is reported. For example, if the
     * kind is {@link Zipkin\Kind\SERVER}, the span's start timestamp is implicitly annotated as "sr"
     * and that plus its duration as "ss".
     *
     * @param string $kind
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setKind($kind)
    {
        if ($kind !== (string) $kind) {
            throw new InvalidArgumentException(
                sprintf('Invalid kind. expected string, got %s', gettype($kind))
            );
        }

        $this->recorder->setKind($this->traceContext, $kind);
    }

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
    public function tag($key, $value)
    {
        $this->recorder->tag($this->traceContext, $key, $value);
    }

    /**
     * Associates an event that explains latency with the current system time.
     *
     * @param string $value A short tag indicating the event, like "finagle.retry"
     * @param int|null $timestamp
     * @return void
     * @throws \InvalidArgumentException
     * @see Annotations
     */
    public function annotate($value, $timestamp = null)
    {
        if (empty($value) || !is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
            throw new InvalidArgumentException('Invalid annotation value');
        }

        if (!Timestamp\isValid($timestamp)) {
            throw new InvalidArgumentException(
                sprintf('Valid timestamp represented microtime expected, got \'%s\'', $timestamp)
            );
        }

        $this->recorder->annotate($this->traceContext, $timestamp, $value);
    }

    /**
     * For a client span, this would be the server's address.
     *
     * It is often expensive to derive a remote address: always check {@link #isNoop()} first!
     *
     * @param Endpoint $remoteEndpoint
     * @return void
     */
    public function setRemoteEndpoint(Endpoint $remoteEndpoint)
    {
        $this->recorder->setRemoteEndpoint($this->traceContext, $remoteEndpoint);
    }

    /**
     * Throws away the current span without reporting it.
     *
     * @return void
     */
    public function abandon()
    {
        $this->recorder->abandon($this->traceContext);
    }

    /**
     * Like {@link #finish()}, except with a given timestamp in microseconds.
     *
     * {@link zipkin.Span#duration Zipkin's span duration} is derived by subtracting the start
     * timestamp from this, and set when appropriate.
     *
     * @param int|null $timestamp
     * @return void
     * @throws \InvalidArgumentException
     */
    public function finish($timestamp = null)
    {
        if ($timestamp !== null && !Timestamp\isValid($timestamp)) {
            throw new InvalidArgumentException('Invalid timestamp');
        }

        if ($timestamp === null) {
            $timestamp = Timestamp\now();
        }

        $this->recorder->finish($this->traceContext, $timestamp);
    }

    /**
     * Reports the span, even if unfinished. Most users will not call this method.
     *
     * This primarily supports two use cases: one-way spans and orphaned spans.
     * For example, a one-way span can be modeled as a span where one flusher calls start and another
     * calls finish. In order to report that span from its origin, flush must be called.
     *
     * Another example is where a user didn't call finish within a deadline or before a shutdown
     * occurs. By flushing, you can report what was in progress.
     *
     * @return void
     */
    public function flush()
    {
        $this->recorder->flush($this->traceContext);
    }
}
