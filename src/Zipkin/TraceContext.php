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

    private function __construct($traceId, $spanId, $parentId, $isSampled, $debug)
    {
        $this->traceId = $traceId;
        $this->spanId = $spanId;
        $this->parentId = $parentId;
        $this->isSampled = $isSampled;
        $this->isDebug = $debug;
    }

    /**
     * @param string $traceId
     * @param string $spanId
     * @param string|null $parentId
     * @param bool|null $isSampled
     * @param bool $isDebug
     * @return TraceContext
     * @throws \InvalidArgumentException
     */
    public static function create($traceId, $spanId, $parentId = null, $isSampled = null, $isDebug = false)
    {
        if (!self::isValidSpanId($spanId)) {
            throw new InvalidArgumentException(sprintf('Invalid span id, got %s', $spanId));
        }

        if (!self::isValidTraceId($traceId)) {
            throw new InvalidArgumentException(sprintf('Invalid trace id, got %s', $traceId));
        }

        if (!self::isValidSpanId($parentId)) {
            throw new InvalidArgumentException(sprintf('Invalid parent span id, got %s', $parentId));
        }

        return new self($traceId, $spanId, $parentId, $isSampled, $isDebug);
    }

    /**
     * @param SamplingFlags|null $samplingFlags
     * @return TraceContext
     */
    public static function createAsRoot(SamplingFlags $samplingFlags = null, $traceId128bits = false)
    {
        if ($samplingFlags === null) {
            $samplingFlags = DefaultSamplingFlags::createAsEmpty();
        }

        $nextId = self::nextId();
        if ($traceId128bits) {
            $traceId = self::traceIdWith128bits();
        } else {
            $traceId = $nextId;
        }

        return new TraceContext(
            $traceId,
            $nextId,
            null,
            $samplingFlags->isSampled(),
            $samplingFlags->isDebug()
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
            $parent->isDebug
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
     * @return bool
     */
    public function isTraceId128bits()
    {
        return $this->traceId128bits;
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
            $this->spanId,
            $this->parentId,
            $isSampled,
            $this->isDebug
        );
    }

    private static function traceIdWith128bits()
    {
        return bin2hex(openssl_random_pseudo_bytes(16));
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
