<?php

namespace Zipkin\Propagation;

interface SamplingFlags
{
    /**
     * @return bool
     */
    public function getSampled();

    /**
     * @return bool
     */
    public function debug();
}
