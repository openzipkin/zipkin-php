<?php

namespace Zipkin\Propagation;

use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\Propagation\Exceptions\InvalidTraceContextArgument;

final class TraceContext implements SamplingFlags
{
    /**
     * @var bool|null
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
     * @throws InvalidTraceContextArgument
     */
    public static function create(
        $traceId,
        $spanId,
        $parentId = null,
        $isSampled = SamplingFlags::EMPTY_SAMPLED,
        $isDebug = SamplingFlags::EMPTY_DEBUG,
        $usesTraceId128bits = false
    ) {
        if (!Id\isValidTraceId($traceId)) {
            throw InvalidTraceContextArgument::forTraceId($traceId);
        }

        if (!Id\isValidSpanId($spanId)) {
            throw InvalidTraceContextArgument::forSpanId($spanId);
        }

        if ($parentId !== null && !Id\isValidSpanId($parentId)) {
            throw InvalidTraceContextArgument::forParentSpanId($parentId);
        }

        if ($isSampled !== null && $isSampled !== (bool) $isSampled) {
            throw InvalidTraceContextArgument::forSampled($isSampled);
        }

        if ($isDebug !== (bool) $isDebug) {
            throw InvalidTraceContextArgument::forDebug($isDebug);
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

        $nextId = Id\generateNextId();

        $traceId = $nextId;
        if ($usesTraceId128bits) {
            $traceId = Id\generateTraceIdWith128bits();
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
        $nextId = Id\generateNextId();

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
}
