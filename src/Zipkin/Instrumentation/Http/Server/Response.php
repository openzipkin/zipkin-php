<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server;

use Zipkin\Instrumentation\Http\Response as HttpResponse;

abstract class Response extends HttpResponse
{
    /**
     * {@inheritdoc}
     */
    public function getRequest(): ?Request
    {
        return null;
    }
}
