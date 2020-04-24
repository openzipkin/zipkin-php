<?php

namespace Zipkin\Propagation;

use ArrayAccess;
use Zipkin\Propagation\Exceptions\InvalidPropagationCarrier;
use Zipkin\Propagation\Exceptions\InvalidPropagationKey;

final class Map implements Getter, Setter
{
    /**
     * {@inheritdoc}
     *
     * If the carrier is an array, the lookup is case insensitive, this is
     * mainly because Map getter is commonly used for those cases where the
     * HTTP framework does not follow the PSR request/response objects (e.g.
     * Symfony) and thus the header bag should be treated as a map. ArrayAccess
     * can't be case insensitive because we can not know the keys on beforehand.
     *
     * @param array|ArrayAccess $carrier
     */
    public function get($carrier, $key)
    {
        $lKey = strtolower($key);

        if ($carrier instanceof ArrayAccess) {
            return $carrier->offsetExists($lKey) ? $carrier->offsetGet($lKey) : null;
        }

        if (is_array($carrier)) {
            if (empty($carrier)) {
                return null;
            }

            $lcCarrier = array_change_key_case($carrier, CASE_LOWER);
            return array_key_exists($lKey, $lcCarrier) ? $lcCarrier[$lKey] : null;
        }

        throw InvalidPropagationCarrier::forCarrier($carrier);
    }

    /**
     * {@inheritdoc}
     * @param array|ArrayAccess $carrier
     */
    public function put(&$carrier, $key, $value)
    {
        if ($key !== (string) $key) {
            throw InvalidPropagationKey::forInvalidKey($key);
        }

        if ($key === '') {
            throw InvalidPropagationKey::forEmptyKey();
        }

        // Lowercasing the key was a first attempt to be compatible with the
        // getter when using the Map getter for HTTP headers.
        $lKey = strtolower($key);

        if ($carrier instanceof ArrayAccess || is_array($carrier)) {
            $carrier[$lKey] = $value;
            return;
        }

        throw InvalidPropagationCarrier::forCarrier($carrier);
    }
}
