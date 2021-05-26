<?php

declare(strict_types=1);

namespace ZipkinTests\Unit\Instrumentation\Http\Server\Psr15;

use Zipkin\Instrumentation\Http\Server\Psr15\Request;
use ZipkinTests\Unit\Instrumentation\Http\Server\BaseRequestTest;
use Nyholm\Psr7\Request as Psr7Request;

final class RequestTest extends BaseRequestTest
{
    public static function createRequest(
        string $method,
        string $uri,
        $headers = [],
        $body = null,
        $route = null
    ): array {
        $delegateRequest = new Psr7Request($method, $uri, $headers, $body);
        return [new Request($delegateRequest), $delegateRequest];
    }
}
