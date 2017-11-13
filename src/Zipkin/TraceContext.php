<?php

namespace Zipkin;

use InvalidArgumentException;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Propagation\SamplingFlags;

final class TraceContext implements SamplingFlags
{
    /**
     * @var bool
     */
    private $isSampled;

    /**
     * @var bool
     */
    private $isDebug;

    /**
     * @var string
     */
    private $traceId;

    /**
     * @var string
     */
    private $spanId;

    /**
     * @var string
     */
    private $parentId;

    /**
     * @var string[]
     */
    private $extra = [];

    private function __construct($traceId, $spanId, $parentId, $isSampled, $isDebug, array $extra)
    {
        $this->traceId = $traceId;
        $this->spanId = $spanId;
        $this->parentId = $parentId;
        $this->isSampled = $isSampled;
        $this->isDebug = $isDebug;
        $this->extra = $extra;
    }

    /**
     * @param string $traceId
     * @param string $spanId
     * @param string|null $parentId
     * @param bool|null $isSampled
     * @param bool $isDebug
     * @param array $extra
     * @return TraceContext
     * @throws \InvalidArgumentException
     */
    public static function create(
        $traceId,
        $spanId,
        $parentId = null,
        $isSampled = null,
        $isDebug = false,
        array $extra = []
    ) {
        if (!self::isValidSpanId($spanId)) {
            throw new InvalidArgumentException(sprintf('Invalid span id, got %s', $spanId));
        }

        if (!self::isValidTraceId($traceId)) {
            throw new InvalidArgumentException(sprintf('Invalid trace id, got %s', $traceId));
        }

        if (!self::isValidSpanId($parentId)) {
            throw new InvalidArgumentException(sprintf('Invalid parent span id, got %s', $parentId));
        }

        return new self($traceId, $spanId, $parentId, $isSampled, $isDebug, $extra);
    }

    /**
     * @param SamplingFlags|null $samplingFlags
     * @return TraceContext
     */
    public static function createAsRoot(SamplingFlags $samplingFlags = null)
    {
        if ($samplingFlags === null) {
            $samplingFlags = DefaultSamplingFlags::createAsEmpty();
        }

        $nextId = self::nextId();

        return new TraceContext(
            $nextId,
            $nextId,
            null,
            $samplingFlags->isSampled(),
            $samplingFlags->isDebug(),
            []
        );
    }

    /**
     * @param TraceContext $parent
     * @return TraceContext
     */
    public static function createFromParent(TraceContext $parent)
    {
        $nextId = self::nextId();

        return new TraceContext(
            $parent->traceId,
            $nextId,
            $parent->spanId,
            $parent->isSampled,
            $parent->isDebug,
            $parent->extra
        );
    }

    /**
     * @return bool|null
     */
    public function isSampled()
    {
        return $this->isSampled;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->isDebug;
    }

    /**
     * Unique 8-byte identifier for a trace, set on all spans within it.
     *
     * @return string
     */
    public function getTraceId()
    {
        return $this->traceId;
    }

    /**
     * Unique 8-byte identifier of this span within a trace.
     *
     * <p>A span is uniquely identified in storage by ({@linkplain #traceId}, {@linkplain #spanId}).
     *
     * @return string
     */
    public function getSpanId()
    {
        return $this->spanId;
    }

    /**
     * The parent's {@link #spanId} or null if this the root span in a trace.
     *
     * @return string
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param string $isSampled
     * @return TraceContext
     */
    public function withSampled($isSampled)
    {
        return new TraceContext(
            $this->traceId,
            $this->parentId,
            $this->spanId,
            $isSampled,
            $this->isDebug,
            $this->extra
        );
    }

    public function withExtra(array $extra)
    {
        return new TraceContext(
            $this->traceId,
            $this->parentId,
            $this->spanId,
            $this->isSampled,
            $this->isDebug,
            $extra
        );
    }

    /**
     * Returns a list of additional data propagated through this trace.
     *
     * <p>The contents are intentionally opaque, deferring to {@linkplain Propagation} to define. An
     * example implementation could be storing a class containing a correlation value, which is
     * extracted from incoming requests and injected as-is onto outgoing requests.
     *
     * @return string[]
     */
    public function getExtra()
    {
        return $this->extra;
    }

    private static function nextId()
    {
        return bin2hex(openssl_random_pseudo_bytes(8));
    }

    private static function isValidTraceId($value)
    {
        return ctype_xdigit((string) $value) &&
        (strlen((string) $value) === 16 || strlen((string) $value) === 32);
    }

    private static function isValidSpanId($value)
    {
        return ctype_xdigit((string) $value) && strlen((string) $value) === 16;
    }
}
