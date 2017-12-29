<?php

namespace Zipkin\Propagation;

use InvalidArgumentException;

final class DefaultSamplingFlags implements SamplingFlags
{
    const EMPTY_SAMPLED = null;
    const EMPTY_DEBUG = false;

    /**
     * @var bool|null
     */
    private $isSampled;

    /**
     * @var bool
     */
    private $isDebug;

    private function __construct($isSampled, $isDebug)
    {
        $this->isSampled = $isSampled;
        $this->isDebug = $isDebug;
    }

    /**
     * @param bool $sampled
     * @param bool $debug
     * @throws InvalidArgumentException
     * @return DefaultSamplingFlags
     */
    public static function create($sampled, $debug = false)
    {
        if ($sampled !== null && $sampled !== (bool) $sampled) {
            throw new InvalidArgumentException(sprintf('sampled should be boolean, got %s', gettype($sampled)));
        }

        if ($debug !== (bool) $debug) {
            throw new InvalidArgumentException(sprintf('debug should be boolean, got %s', gettype($debug)));
        }

        return new self($sampled, $debug);
    }

    /**
     * @return DefaultSamplingFlags
     */
    public static function createAsEmpty()
    {
        return new self(self::EMPTY_SAMPLED, self::EMPTY_DEBUG);
    }

    /**
     * @return DefaultSamplingFlags
     */
    public static function createAsSampled()
    {
        return new self(true, false);
    }

    /**
     * @return DefaultSamplingFlags
     */
    public static function createAsNotSampled()
    {
        return new self(false, false);
    }

    /**
     * @return DefaultSamplingFlags
     */
    public static function createAsDebug()
    {
        return new self(true, true);
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
     * @param SamplingFlags $samplingFlags
     * @return bool
     */
    public function isEqual(SamplingFlags $samplingFlags)
    {
        return $this->isDebug() === $samplingFlags->isDebug()
            && $this->isSampled() === $samplingFlags->isSampled();
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
