<?php

namespace Zipkin\Propagation;

interface Getter
{
    /**
     * @param $carrier
     * @param string $key
     * @return string
     */
    public function get($carrier, $key);
}
