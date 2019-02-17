<?php

namespace Zipkin\Propagation;

interface Setter
{
    /**
     * Replaces a propagated key with the given value
     *
     * @param mixed $carrier
     * @param string $key
     * @param string $value
     * @return void
     */
    public function put(&$carrier, string $key, string $value);
}
