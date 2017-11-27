<?php

namespace Zipkin\Recording;

use Zipkin\Endpoint;
use Zipkin\Propagation\TraceContext;

final class Span
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
     *
     * @var int
     */
    private $timestamp;

    /**
     * @var bool
     */
    private $finished = false;

    /**
     * Span name in lowercase, rpc method for example. Conventionally, when the
     * span name isn't known, name = "unknown".
     *
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $kind;

    /**
     * Unique 8-byte identifier for a trace, set on all spans within it.
     *
     * @var int
     */
    private $traceId;

    /**
     * The parent's Span.id; absent if this the root span in a trace.
     *
     * @var int
     */
    private $parentId;

    /**
     * Unique 8-byte identifier of this span within a trace. A span is uniquely
     * identified in storage by (trace_id, id).
     *
     * @var int
     */
    private $spanId;

    /**
     * True is a request to store this span even if it overrides sampling policy.
     *
     * @var bool
     */
    private $debug;

    /**
     * Associates events that explain latency with a timestamp. Unlike log
     * statements, annotations are often codes: for example SERVER_RECV("sr").
     * Annotations are sorted ascending by timestamp.
     *
     * @var string[][]
     */
    private $annotations = [];

    /**
     * @var array
     */
    private $tags = [];

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
     *
     * @var int
     */
    private $duration;

    /**
     * @var Endpoint
     */
    private $remoteEndpoint;

    /**
     * @var Endpoint
     */
    private $localEndpoint;

    private function __construct($traceId, $parentId, $spanId, $debug, Endpoint $localEndpoint)
    {
        $this->traceId = $traceId;
        $this->parentId = $parentId;
        $this->spanId = $spanId;
        $this->debug = $debug;
        $this->localEndpoint = $localEndpoint;
    }

    /**
     * @param TraceContext $context
     * @param Endpoint $localEndpoint
     * @return Span
     */
    public static function createFromContext(TraceContext $context, Endpoint $localEndpoint)
    {
        return new self(
            $context->getTraceId(),
            $context->getParentId(),
            $context->getSpanId(),
            $context->isDebug(),
            $localEndpoint
        );
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     * @return void
     */
    public function start($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $kind
     * @return void
     */
    public function setKind($kind)
    {
        $this->kind = $kind;
    }

    /**
     * @param int $timestamp
     * @param string $value
     * @throws \InvalidArgumentException
     */
    public function annotate($timestamp, $value)
    {
        $this->annotations[] = [
            'value' => $value,
            'timestamp' => $timestamp,
        ];
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function tag($key, $value)
    {
        $this->tags[$key] = $value;
    }

    /**
     * @param Endpoint $remoteEndpoint
     * @return void
     */
    public function setRemoteEndpoint(Endpoint $remoteEndpoint)
    {
        $this->remoteEndpoint = $remoteEndpoint;
    }

    /**
     * Completes and reports the span
     *
     * @param int|null $finishTimestamp
     */
    public function finish($finishTimestamp = null)
    {
        if ($this->finished) {
            return;
        }

        if ($this->timestamp !== null && $finishTimestamp !== null) {
            $this->duration = $finishTimestamp - $this->timestamp;
        }

        $this->finished = true;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $spanAsArray = [
            'id' => (string) $this->spanId,
            'name' => $this->name,
            'traceId' => (string) $this->traceId,
            'parentId' => $this->parentId ? (string) $this->parentId : null,
            'timestamp' => $this->timestamp,
            'duration' => $this->duration,
            'debug' => $this->debug,
            'localEndpoint' => $this->localEndpoint->toArray(),
        ];

        if ($this->kind !== null) {
            $spanAsArray['kind'] = $this->kind;
        }

        if ($this->remoteEndpoint !== null) {
            $spanAsArray['remoteEndpoint'] = $this->remoteEndpoint->toArray();
        }

        if (!empty($this->annotations)) {
            $spanAsArray['annotations'] = $this->annotations;
        }

        if (!empty($this->tags)) {
            $spanAsArray['tags'] = $this->tags;
        }

        return $spanAsArray;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }
}
