<?php

namespace Zipkin\Propagation;

use ArrayAccess;
use Zipkin\Propagation\Exceptions\InvalidPropagationCarrier;
use Zipkin\Propagation\Exceptions\InvalidPropagationKey;

final class Map implements Getter, Setter
{
    /**
     * {@inheritdoc}
     * @param array|ArrayAccess $carrier
     */
    public function get($carrier, string $key): ?string
    {
        $lKey = strtolower($key);

        if ($carrier instanceof ArrayAccess) {
            return $carrier->offsetExists($lKey) ? $carrier->offsetGet($lKey) : null;
        }

        if (is_array($carrier)) {
            return array_key_exists($lKey, $carrier) ? $carrier[$lKey] : null;
        }

        throw InvalidPropagationCarrier::forCarrier($carrier);
    }

    /**
     * {@inheritdoc}
     * @param array|ArrayAccess $carrier
     */
    public function put(&$carrier, string $key, string $value)
    {
        if ($key === '') {
            throw InvalidPropagationKey::forEmptyKey();
        }

        $lKey = strtolower($key);

        if ($carrier instanceof ArrayAccess || is_array($carrier)) {
            $carrier[$lKey] = $value;
            return;
        }

        throw InvalidPropagationCarrier::forCarrier($carrier);
    }
}
