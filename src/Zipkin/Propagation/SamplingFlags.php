<?php

namespace Zipkin\Propagation;

interface SamplingFlags
{
    /**
     * @return bool
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
