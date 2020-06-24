<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Zipkin\Propagation\RequestHeaders as BaseRequestHeaders;
use Zipkin\Propagation\RemoteSetter;
use Zipkin\Kind;

final class RequestHeaders extends BaseRequestHeaders implements RemoteSetter
{
    public function getKind(): string
    {
        return Kind\CLIENT;
    }
}
