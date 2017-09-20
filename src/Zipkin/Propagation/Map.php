<?php

namespace Zipkin\Propagation;

use ArrayAccess;
use Zipkin\Propagation\Exceptions\InvalidPropagationCarrier;
use Zipkin\Propagation\Exceptions\InvalidPropagationKey;

final class Map implements Getter, Setter
{
    /**
     * @param array|ArrayAccess $carrier
     * @param string $key
     * @return string
     * @throws InvalidPropagationCarrier
     */
    public function get($carrier, $key)
    {
        if (is_array($carrier)) {
            return array_key_exists($key, $carrier) ? $carrier[$key] : null;
        }

        if ($carrier instanceof ArrayAccess) {
            return $carrier->offsetExists($key) ? $carrier->offsetGet($key) : null;
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

        if ($carrier instanceof ArrayAccess) {
            $carrier[$key] = $value;
            return;
        }

        throw InvalidPropagationCarrier::forCarrier($carrier);
    }
}
