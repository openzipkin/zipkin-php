<?php

namespace Zipkin\Propagation;

use ArrayAccess;
use Zipkin\Propagation\Exceptions\InvalidPropagationCarrier;
use Zipkin\Propagation\Exceptions\InvalidPropagationKey;

final class Map implements Getter, Setter
{
    /**
     * @param ArrayAccess $carrier
     * @param string $key
     * @return string
     * @throws InvalidPropagationCarrier
     */
    public function get($carrier, $key)
    {
        $lKey = strtolower($key);

        if ($carrier instanceof ArrayAccess) {
            return $carrier->offsetExists($lKey) ? $carrier->offsetGet($lKey) : null;
        }

        throw InvalidPropagationCarrier::forCarrier($carrier);
    }

    /**
     * @param ArrayAccess $carrier
     * @param string $key
     * @param string $value
     * @return void
     * @throws InvalidPropagationCarrier
     * @throws InvalidPropagationKey
     */
    public function put($carrier, $key, $value)
    {
        if ($key !== (string) $key) {
            throw InvalidPropagationKey::forInvalidKey($key);
        }

        if ($key === '') {
            throw InvalidPropagationKey::forEmptyKey();
        }

        $lKey = strtolower($key);

        if ($carrier instanceof ArrayAccess) {
            $carrier[$lKey] = $value;
            return;
        }

        throw InvalidPropagationCarrier::forCarrier($carrier);
    }
}
