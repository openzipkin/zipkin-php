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
        // We return the first value becase we relay on the fact that we
        // always override the header value when put method is called.
        return $carrier->hasHeader($key) ? $carrier->getHeader($key)[0] : null;
    }

    /**
     * {@inheritdoc}
     *
     * @param RequestInterface $carrier
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function put(&$carrier, string $key, string $value): void
    {
        $carrier = $carrier->withHeader(\strtolower($key), $value);
    }
}
