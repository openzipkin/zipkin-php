<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server;

use Zipkin\Tags;
use Zipkin\SpanCustomizer;
use Zipkin\Propagation\TraceContext;
use Throwable;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class DefaultParser implements Parser
{
    public function spanName(ServerRequestInterface $request): string
    {
        return $request->getMethod();
    }

    public function request(ServerRequestInterface $request, TraceContext $context, SpanCustomizer $span): void
    {
        $span->tag(Tags\HTTP_METHOD, $request->getMethod());
        $span->tag(Tags\HTTP_PATH, $request->getUri()->getPath() ?: "/");
    }

    public function response(ResponseInterface $response, TraceContext $context, SpanCustomizer $span): void
    {
        $span->tag(Tags\HTTP_STATUS_CODE, (string) $response->getStatusCode());
        if ($response->getStatusCode() > 399) {
            $span->tag(Tags\ERROR, (string) $response->getStatusCode());
        }
    }
}
