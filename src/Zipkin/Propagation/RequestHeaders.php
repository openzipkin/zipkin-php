<?php

declare(strict_types=1);

namespace Zipkin\Propagation;

use Psr\Http\Message\RequestInterface;

final class RequestHeaders implements Getter, Setter
{
    /**
     * {@inheritdoc}
     *
     * @param RequestInterface $carrier
     */
    public function get($carrier, string $key): ?string
    {
        $lKey = \strtolower($key);

        // We return the first value becase we relay on the fact that we
        // always override the header value when put method is called.
        return $carrier->hasHeader($lKey) ? $carrier->getHeader($lKey)[0] : null;
    }

    /**
     * {@inheritdoc}
     *
     * @param RequestInterface $carrier
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function put(&$carrier, string $key, string $value): void
    {
        $lKey = \strtolower($key);
        $carrier = $carrier->withHeader($lKey, $value);
    }
}
