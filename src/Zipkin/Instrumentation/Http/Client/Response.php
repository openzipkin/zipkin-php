<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Zipkin\Instrumentation\Http\Response as HttpResponse;

/**
 * {@inheritdoc}
 */
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
