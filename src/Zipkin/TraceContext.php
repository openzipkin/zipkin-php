<?php

namespace Zipkin;

use Zipkin\Propagation\SamplingFlags;

final class TraceContext implements SamplingFlags
{
    /**
     * @var bool
     */
    private $sampled;

    /**
     * @var bool
     */
    private $debug;

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
        $this->sampled = $isSampled;
        $this->debug = $debug;
    }

    public static function createAsRoot(SamplingFlags $samplingFlags)
    {
        $nextId = self::nextId();

        return new TraceContext(
            $nextId,
            $nextId,
            null,
            $samplingFlags->getSampled(),
            $samplingFlags->debug()
        );
    }

    public static function createFromParent(TraceContext $parent)
    {
        $nextId = self::nextId();

        return new TraceContext(
            $parent->traceId,
            $nextId,
            $parent->spanId,
            $parent->sampled,
            $parent->debug
        );
    }

    /**
     * @return bool
     */
    public function debug()
    {
        return $this->debug;
    }

    /**
     * Unique 8-byte identifier for a trace, set on all spans within it.
     *
     * @return mixed
     */
    public function getTraceId()
    {
        return $this->traceId;
    }

    /**
     * @param $traceId
     */
    public function setTraceId($traceId)
    {
        $this->traceId = $traceId;
    }

    /**
     * Unique 8-byte identifier of this span within a trace.
     *
     * <p>A span is uniquely identified in storage by ({@linkplain #traceId}, {@linkplain #spanId}).
     *
     * @return mixed
     */
    public function getSpanId()
    {
        return $this->spanId;
    }

    public function setSpanId($spanId)
    {
        $this->spanId = $spanId;
    }

    /**
     * The parent's {@link #spanId} or null if this the root span in a trace.
     *
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    }

    /**
     * @return bool|null
     */
    public function getSampled()
    {
        return $this->sampled;
    }

    /**
     * @param bool $sampled
     */
    public function setSampled($sampled)
    {
        $this->sampled = $sampled;
    }

    public function withSampled($isSampled)
    {
        return new TraceContext(
            $this->traceId,
            $this->parentId,
            $this->spanId,
            $isSampled,
            $this->debug
        );
    }

    private static function nextId()
    {
        return bin2hex(openssl_random_pseudo_bytes(8));
    }
}
