<?php

declare(strict_types=1);

namespace Zipkin\Propagation;

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

    private function __construct(?bool $isSampled, bool $isDebug)
    {
        $this->isSampled = $isSampled;
        $this->isDebug = $isDebug;
    }

    /**
     * @param bool|null $isSampled
     * @param bool $isDebug
     * @return DefaultSamplingFlags
     */
    public static function create(?bool $isSampled, bool $isDebug = false): self
    {
        return new self($isSampled, $isDebug);
    }

    /**
     * @return DefaultSamplingFlags
     */
    public static function createAsEmpty(): self
    {
        return new self(SamplingFlags::EMPTY_SAMPLED, SamplingFlags::EMPTY_DEBUG);
    }

    /**
     * @return DefaultSamplingFlags
     */
    public static function createAsSampled(): self
    {
        return new self(true, false);
    }

    /**
     * @return DefaultSamplingFlags
     */
    public static function createAsNotSampled(): self
    {
        return new self(false, false);
    }

    /**
     * @return DefaultSamplingFlags
     */
    public static function createAsDebug(): self
    {
        return new self(null, true);
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

    public function isEmpty(): bool
    {
        return $this->isSampled === self::EMPTY_SAMPLED &&
        $this->isDebug === self::EMPTY_DEBUG;
    }

    /**
     * @param SamplingFlags $samplingFlags
     * @return bool
     */
    public function isEqual(SamplingFlags $samplingFlags): bool
    {
        return $this->isDebug() === $samplingFlags->isDebug()
            && $this->isSampled() === $samplingFlags->isSampled();
    }
}
