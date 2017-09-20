<?php

namespace Zipkin\Propagation;

interface Setter
{
    /**
     * @param $carrier
     * @param string $key
     * @param string $value
     * @return void
     */
    public function put($carrier, $key, $value);
}
