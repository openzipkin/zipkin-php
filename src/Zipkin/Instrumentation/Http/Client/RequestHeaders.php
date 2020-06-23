<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Zipkin\Kind;
use Zipkin\Propagation\RemoteSetter;
use Zipkin\Propagation\RequestHeaders as BaseRequestHeaders;

final class RequestHeaders extends BaseRequestHeaders implements RemoteSetter
{
    public function getKind(): string
    {
        return Kind\CLIENT;
    }
}
