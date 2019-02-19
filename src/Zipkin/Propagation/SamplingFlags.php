<?php

declare(strict_types=1);

namespace Zipkin\Propagation;

interface SamplingFlags
{
    const EMPTY_SAMPLED = null;
    const EMPTY_DEBUG = false;

    /**
     * @return bool|null
     */
    public function isSampled(): ?bool;

    /**
     * @return bool
     */
    public function isDebug(): bool;

    /**
     * @param SamplingFlags $samplingFlags
     * @return bool
     */
    public function isEqual(SamplingFlags $samplingFlags): bool;
}
