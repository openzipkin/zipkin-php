<?php

namespace Zipkin\Propagation;

use InvalidArgumentException;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Propagation\SamplingFlags;

final class TraceContext implements SamplingFlags
{
    const EMPTY_SAMPLED = null;
    const EMPTY_DEBUG = false;

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
     * @var bool
     */
    private $usesTraceId128bits;

    private function __construct($traceId, $spanId, $parentId, $isSampled, $isDebug, $usesTraceId128bits)
    {
        $this->traceId = $traceId;
        $this->spanId = $spanId;
        $this->parentId = $parentId;
        $this->isSampled = $isSampled;
        $this->isDebug = $isDebug;
        $this->usesTraceId128bits = $usesTraceId128bits;
    }

    /**
     * @param string $traceId
     * @param string $spanId
     * @param string|null $parentId
     * @param bool|null $isSampled
     * @param bool $isDebug
     * @param bool $usesTraceId128bits
     * @return TraceContext
     * @throws \InvalidArgumentException
     */
    public static function create(
        $traceId,
        $spanId,
        $parentId = null,
        $isSampled = null,
        $isDebug = false,
        $usesTraceId128bits = false
    ) {
        if (!self::isValidTraceId($traceId)) {
            throw new InvalidArgumentException(sprintf('Invalid trace id, got %s', $traceId));
        }

        if (!self::isValidSpanId($spanId)) {
            throw new InvalidArgumentException(sprintf('Invalid span id, got %s', $spanId));
        }

        if ($parentId !== null && !self::isValidSpanId($parentId)) {
            throw new InvalidArgumentException(sprintf('Invalid parent span id, got %s', $parentId));
        }

        return new self($traceId, $spanId, $parentId, $isSampled, $isDebug, $usesTraceId128bits);
    }

    /**
     * @param SamplingFlags|null $samplingFlags
     * @param bool $usesTraceId128bits
     * @return TraceContext
     */
    public static function createAsRoot(SamplingFlags $samplingFlags = null, $usesTraceId128bits = false)
    {
        if ($samplingFlags === null) {
            $samplingFlags = DefaultSamplingFlags::createAsEmpty();
        }

        $nextId = self::nextId();

        $traceId = $nextId;
        if ($usesTraceId128bits) {
            $traceId = self::traceIdWith128bits();
        }

        return new TraceContext(
            $traceId,
            $nextId,
            null,
            $samplingFlags->isSampled(),
            $samplingFlags->isDebug(),
            $usesTraceId128bits
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
            $parent->usesTraceId128bits
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
    public function usesTraceId128bits()
    {
        return $this->usesTraceId128bits;
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
     * @return string|null
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
            $this->isDebug,
            $this->usesTraceId128bits
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

    /**
     * @param SamplingFlags $samplingFlags
     * @return bool
     */
    public function isEqual(SamplingFlags $samplingFlags)
    {
        return ($samplingFlags instanceof TraceContext)
            && $this->traceId === $samplingFlags->traceId
            && $this->spanId === $samplingFlags->spanId
            && $this->parentId === $samplingFlags->parentId
            && $this->isSampled === $samplingFlags->isSampled
            && $this->isDebug === $samplingFlags->isDebug
            && $this->usesTraceId128bits === $samplingFlags->usesTraceId128bits;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->isSampled === self::EMPTY_SAMPLED
            && $this->isDebug === self::EMPTY_DEBUG;
    }
}
