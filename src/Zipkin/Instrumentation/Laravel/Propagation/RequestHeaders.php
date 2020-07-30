<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Laravel\Propagation;

use Zipkin\Propagation\Getter;
use Illuminate\Http\Request;

final class RequestHeaders implements Getter
{
    /**
     * {@inheritdoc}
     *
     * @param Request $carrier
     */
    public function get($carrier, string $key): ?string
    {
        return $carrier->hasHeader($key) ? $carrier->header($key)[0] : null;
    }
}
