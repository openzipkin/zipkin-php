<?php

namespace Zipkin\Propagation;

use Zipkin\Propagation\Exceptions\InvalidPropagationValue;

interface Setter
{
    /**
     * Replaces a propagated key with the given value
     *
     * @param $carrier
     * @param string $key
     * @param string $value
     * @return void
     * @throws InvalidPropagationValue if the value is not a string
     */
    public function put($carrier, $key, $value);
}
