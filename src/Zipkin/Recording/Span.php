<?php

declare(strict_types=1);

namespace Zipkin\Recording;

use Zipkin\Propagation\TraceContext;
use Zipkin\Endpoint;
use Throwable;

final class Span implements ReadbackSpan
{
    /**
     * Epoch microseconds of the start of this span, absent if this an incomplete
     * span.
     *
     * This value should be set directly by instrumentation, using the most
     * precise value possible. For example, gettimeofday or syncing nanoTime
     * against a tick of currentTimeMillis.
     *
     * Timestamp is nullable for input only. Spans without a timestamp cannot be
     * presented in a timeline: Span stores should not output spans missing a
     * timestamp.
     *
     * There are two known edge-cases where this could be absent: both cases
     * exist when a collector receives a span in parts and a binary annotation
     * precedes a timestamp. This is possible when..
     *  - The span is in-flight (ex not yet received a timestamp)
     *  - The span's start event was lost
     */
    private int $timestamp = 0;

    private bool $finished = false;

    /**
     * Span name in lowercase, rpc method for example. Conventionally, when the
     * span name isn't known, name = "unknown".
     */
    private ?string $name = null;

    private ?string $kind = null;

    /**
     * Unique 8-byte identifier for a trace, set on all spans within it.
     */
    private string $traceId;

    /**
     * The parent's Span.id; absent if this the root span in a trace.
     */
    private ?string $parentId;

    /**
     * Unique 8-byte identifier of this span within a trace. A span is uniquely
     * identified in storage by (trace_id, id).
     */
    private string $spanId;

    private ?bool $isSampled;

    /**
     * True is a request to store this span even if it overrides sampling policy.
     */
    private bool $debug;

    /**
     * True if we are contributing to a span started by another tracer (ex on a different host).
     */
    private bool $shared;

    /**
     * Associates events that explain latency with a timestamp. Unlike log
     * statements, annotations are often codes: for example SERVER_RECV("sr").
     * Annotations are sorted ascending by timestamp.
     *
     * @var array<array>
     */
    private array $annotations = [];

    private array $tags = [];

    private ?Throwable $error = null;

    /**
     * Measurement in microseconds of the critical path, if known. Durations of
     * less than one microsecond must be rounded up to 1 microsecond.
     *
     * This value should be set directly, as opposed to implicitly via annotation
     * timestamps. Doing so encourages precision decoupled from problems of
     * clocks, such as skew or NTP updates causing time to move backwards.
     *
     * If this field is persisted as unset, zipkin will continue to work, except
     * duration query support will be implementation-specific. Similarly, setting
     * this field non-atomically is implementation-specific.
     *
     * This field is i64 vs i32 to support spans longer than 35 minutes.
     */
    private ?int $duration = null;

    private ?Endpoint $remoteEndpoint = null;

    private Endpoint $localEndpoint;

    private function __construct(
        string $traceId,
        ?string $parentId,
        string $spanId,
        ?bool $isSampled,
        bool $debug,
        bool $shared,
        Endpoint $localEndpoint
    ) {
        $this->traceId = $traceId;
        $this->parentId = $parentId;
        $this->spanId = $spanId;
        $this->isSampled = $isSampled;
        $this->debug = $debug;
        $this->shared = $shared;
        $this->localEndpoint = $localEndpoint;
    }

    /**
     * @param TraceContext $context
     * @param Endpoint $localEndpoint
     * @return Span
     */
    public static function createFromContext(TraceContext $context, Endpoint $localEndpoint): Span
    {
        return new self(
            $context->getTraceId(),
            $context->getParentId(),
            $context->getSpanId(),
            $context->isSampled(),
            $context->isDebug(),
            $context->isShared(),
            $localEndpoint
        );
    }

    public function getSpanId(): string
    {
        return $this->spanId;
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function isSampled(): bool
    {
        return $this->isSampled === true;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function isShared(): bool
    {
        return $this->shared;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getKind(): ?string
    {
        return $this->kind;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function getLocalEndpoint(): Endpoint
    {
        return $this->localEndpoint;
    }

    public function getRemoteEndpoint(): ?Endpoint
    {
        return $this->remoteEndpoint;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getAnnotations(): array
    {
        return $this->annotations;
    }

    public function getError(): ?Throwable
    {
        return $this->error;
    }

    /**
     * @param int $timestamp created by the usage of Timestamp\now
     *
     * @see Zipkin\Timestamp\now
     */
    public function start(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string $kind one of Kind\CLIENT, Kind\SERVER, Kind\CONSUMER and
     * kind\PRODUCER.
     *
     * @see Zipkin\Kind
     */
    public function setKind(string $kind): void
    {
        $this->kind = $kind;
    }

    public function annotate(int $timestamp, string $value): void
    {
        $this->annotations[] = [
            'value' => $value,
            'timestamp' => $timestamp,
        ];
    }

    public function tag(string $key, string $value): void
    {
        $this->tags[$key] = $value;
    }

    public function setError(Throwable $e): void
    {
        $this->error = $e;
    }

    public function setRemoteEndpoint(Endpoint $remoteEndpoint): void
    {
        $this->remoteEndpoint = $remoteEndpoint;
    }

    /**
     * Completes and reports the span. If no finish timestamp is specified
     * we don't compute the duration but the span is still reporterd. This
     * usually happens when a span is flushed manually.
     *
     * @param int|null $finishTimestamp
     */
    public function finish(int $finishTimestamp = null): void
    {
        if ($this->finished) {
            return;
        }

        if ($this->timestamp !== null && $finishTimestamp !== null) {
            $this->duration = $finishTimestamp - $this->timestamp;
        }

        $this->finished = true;
    }
}
