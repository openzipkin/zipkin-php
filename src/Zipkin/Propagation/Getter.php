<?php

namespace Zipkin\Propagation;

interface Getter
{
    /**
     * Gets the first value of the given propagation key or returns null
     *
     * @param $carrier
     * @param string $key
     * @return string|null
     */
    public function get($carrier, $key);
}
