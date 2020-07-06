<?php

declare(strict_types=1);

namespace Zipkin\Propagation;

use function Zipkin\Propagation\Id\generateTraceIdWith128bits;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\Propagation\Exceptions\InvalidTraceContextArgument;
use Zipkin\Propagation\DefaultSamplingFlags;

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
     * @var string|null
     */
    private $parentId;

    /**
     * @var bool
     */
    private $isShared;

    /**
     * @var bool
     */
    private $usesTraceId128bits;

    private function __construct(
        string $traceId,
        string $spanId,
        ?string $parentId,
        ?bool $isSampled,
        bool $isDebug,
        bool $isShared,
        bool $usesTraceId128bits
    ) {
        $this->traceId = $traceId;
        $this->spanId = $spanId;
        $this->parentId = $parentId;
        $this->isSampled = $isDebug ?: $isSampled;
        $this->isDebug = $isDebug;
        $this->isShared = $isShared;
        $this->usesTraceId128bits = $usesTraceId128bits;
    }

    /**
     * @param string $traceId
     * @param string $spanId
     * @param string|null $parentId
     * @param bool|null $isSampled
     * @param bool $isDebug
     * @return TraceContext
     * @throws InvalidTraceContextArgument
     */
    public static function create(
        string $traceId,
        string $spanId,
        ?string $parentId = null,
        ?bool $isSampled = SamplingFlags::EMPTY_SAMPLED,
        bool $isDebug = SamplingFlags::EMPTY_DEBUG,
        bool $isShared = false
    ): TraceContext {
        if (!Id\isValidTraceId($traceId)) {
            throw InvalidTraceContextArgument::forTraceId($traceId);
        }

        if (!Id\isValidSpanId($spanId)) {
            throw InvalidTraceContextArgument::forSpanId($spanId);
        }

        if ($parentId !== null && !Id\isValidSpanId($parentId)) {
            throw InvalidTraceContextArgument::forParentSpanId($parentId);
        }

        return new self($traceId, $spanId, $parentId, $isSampled, $isDebug, $isShared, strlen($traceId) === 32);
    }

    /**
     * @param SamplingFlags|null $samplingFlags
     * @param bool $usesTraceId128bits
     * @return TraceContext
     */
    public static function createAsRoot(?SamplingFlags $samplingFlags = null, bool $usesTraceId128bits = false): self
    {
        if ($samplingFlags === null) {
            $samplingFlags = DefaultSamplingFlags::createAsEmpty();
        }

        $nextId = Id\generateNextId();

        $traceId = $nextId;
        if ($usesTraceId128bits) {
            $traceId = generateTraceIdWith128bits();
        }

        return new self(
            $traceId,
            $nextId,
            null,
            $samplingFlags->isSampled(),
            $samplingFlags->isDebug(),
            false,
            $usesTraceId128bits
        );
    }

    /**
     * @param TraceContext $parent
     * @return TraceContext
     */
    public static function createFromParent(TraceContext $parent): self
    {
        $nextId = Id\generateNextId();

        return new TraceContext(
            $parent->traceId,
            $nextId,
            $parent->spanId,
            $parent->isSampled,
            $parent->isDebug,
            false,
            $parent->usesTraceId128bits
        );
    }

    /**
     * @return bool|null
     */
    public function isSampled(): ?bool
    {
        return $this->isSampled;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->isDebug;
    }

    /**
     * @return bool
     */
    public function usesTraceId128bits(): bool
    {
        return $this->usesTraceId128bits;
    }

    /**
     * Unique 8-byte identifier for a trace, set on all spans within it.
     *
     * @return string
     */
    public function getTraceId(): string
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
    public function getSpanId(): string
    {
        return $this->spanId;
    }

    /**
     * The parent's {@link #spanId} or null if this the root span in a trace.
     *
     * @return string|null
     */
    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function isShared(): bool
    {
        return $this->isShared;
    }

    /**
     * @return TraceContext
     */
    public function withSampled(bool $isSampled): SamplingFlags
    {
        return new TraceContext(
            $this->traceId,
            $this->spanId,
            $this->parentId,
            $isSampled,
            false,
            $this->usesTraceId128bits,
            $this->isShared
        );
    }

    /**
     * IMPORTANT: This is an internal method.
     *
     * Returns the same context but flagged as shared. This method
     * is only meant to be used by the Tracer::joinSpan and it is
     * only exposed because of the visibility limitations of the
     * language.
     */
    public function withShared(bool $isShared): TraceContext
    {
        return new TraceContext(
            $this->traceId,
            $this->spanId,
            $this->parentId,
            $this->isSampled,
            $this->isDebug,
            $isShared,
            $this->usesTraceId128bits
        );
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function isEqual(SamplingFlags $samplingFlags): bool
    {
        return ($samplingFlags instanceof TraceContext)
            && $this->traceId === $samplingFlags->traceId
            && $this->spanId === $samplingFlags->spanId
            && $this->parentId === $samplingFlags->parentId;
    }
}
