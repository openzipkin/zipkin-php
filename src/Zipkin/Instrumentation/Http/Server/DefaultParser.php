<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server;

use Zipkin\Tags;
use Zipkin\SpanCustomizer;
use Zipkin\Propagation\TraceContext;
use Zipkin\Instrumentation\Http\Server\Parser;

/**
 * DefaultParser contains the basic logic for turning request/response information
 * into span name and tags. Implementors can use this as a base parser to reduce
 * boilerplate.
 */
class DefaultParser implements Parser
{
    public function spanName(Request $request): string
    {
        return $request->getMethod() . ($request->getRoute() === null ? '' : ' ' . $request->getRoute());
    }

    public function request(Request $request, TraceContext $context, SpanCustomizer $span): void
    {
        $span->tag(Tags\HTTP_METHOD, $request->getMethod());
        $span->tag(Tags\HTTP_PATH, $request->getPath() ?: '/');
    }

    public function response(Response $response, TraceContext $context, SpanCustomizer $span): void
    {
        $span->tag(Tags\HTTP_STATUS_CODE, (string) $response->getStatusCode());
        if ($response->getStatusCode() > 399) {
            $span->tag(Tags\ERROR, (string) $response->getStatusCode());
        }

        if ($response->getRoute() !== null && $response->getRequest() !== null) {
            $span->setName($response->getRequest()->getMethod() . ' ' . $response->getRoute());
        }
    }
}
