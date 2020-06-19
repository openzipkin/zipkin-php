<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Throwable;
use Zipkin\Tags;
use Zipkin\SpanCustomizer;
use Zipkin\Propagation\TraceContext;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class DefaultHandler implements Handler
{
    public function spanName(RequestInterface $request): string
    {
        return $request->getMethod();
    }

    public function requestSampler(RequestInterface $request): ?bool
    {
        return null;
    }

    public function parseRequest(RequestInterface $request, TraceContext $context, SpanCustomizer $span): void
    {
        $span->tag(Tags\HTTP_METHOD, $request->getMethod());
        $span->tag(Tags\HTTP_PATH, $request->getUri()->getPath());
    }

    public function parseResponse(ResponseInterface $response, TraceContext $context, SpanCustomizer $span): void
    {
        $span->tag(Tags\HTTP_STATUS_CODE, (string) $response->getStatusCode());
        if ($response->getStatusCode() > 399) {
            $span->tag(Tags\ERROR, (string) $response->getStatusCode());
        }
    }

    public function parseError(Throwable $e, TraceContext $context, SpanCustomizer $span): void
    {
        $span->tag(Tags\ERROR, $e->getMessage());
    }
}
