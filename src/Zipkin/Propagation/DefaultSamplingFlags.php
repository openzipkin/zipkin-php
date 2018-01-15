<?php

namespace Zipkin\Propagation;

use InvalidArgumentException;

final class DefaultSamplingFlags implements SamplingFlags
{
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
     * @param bool|null $isSampled
     * @param bool $isDebug
     * @throws InvalidArgumentException
     * @return DefaultSamplingFlags
     */
    public static function create($isSampled, $isDebug = false)
    {
        if ($isSampled !== null && $isSampled !== (bool) $isSampled) {
            throw new InvalidArgumentException(
                sprintf('isSampled should be boolean or null, got %s', gettype($isSampled))
            );
        }

        if ($isDebug !== (bool) $isDebug) {
            throw new InvalidArgumentException(sprintf('isDebug should be boolean, got %s', gettype($isDebug)));
        }

        return new self($isSampled, $isDebug);
    }

    /**
     * @return DefaultSamplingFlags
     */
    public static function createAsEmpty()
    {
        return new self(SamplingFlags::EMPTY_SAMPLED, SamplingFlags::EMPTY_DEBUG);
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
}
