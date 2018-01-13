<?php

namespace Zipkin\Propagation;

interface SamplingFlags
{
    const EMPTY_SAMPLED = null;
    const EMPTY_DEBUG = false;

    /**
     * @return bool|null
     */
    public function isSampled();

    /**
     * @return bool
     */
    public function isDebug();

    /**
     * @param SamplingFlags $samplingFlags
     * @return bool
     */
    public function isEqual(SamplingFlags $samplingFlags);
}
