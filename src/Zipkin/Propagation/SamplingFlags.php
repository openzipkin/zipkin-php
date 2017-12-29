<?php

namespace Zipkin\Propagation;

interface SamplingFlags
{
    /**
     * @return bool|null
     */
    public function isSampled();

    /**
     * @return bool
     */
    public function isDebug();

    /**
     * @return bool
     */
    public function isEmpty();

    /**
     * @param SamplingFlags $samplingFlags
     * @return bool
     */
    public function isEqual(SamplingFlags $samplingFlags);
}
