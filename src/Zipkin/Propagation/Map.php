<?php

namespace Zipkin\Propagation;

use ArrayAccess;
use Zipkin\Propagation\Exceptions\InvalidPropagationCarrier;
use Zipkin\Propagation\Exceptions\InvalidPropagationKey;

final class Map implements Getter, Setter
{
    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
