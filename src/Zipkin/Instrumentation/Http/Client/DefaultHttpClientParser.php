<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Zipkin\Tags;
use Zipkin\SpanCustomizer;
use Zipkin\Propagation\TraceContext;
use Zipkin\Instrumentation\Http\Response;
use Zipkin\Instrumentation\Http\Request;
use Zipkin\Instrumentation\Http\Client\HttpClientParser;

/**
 * DefaultParser contains the basic logic for turning request/response information
 * into span name and tags. Implementors can use this as a base parser to reduce
 * boilerplate.
 */
class DefaultHttpClientParser implements HttpClientParser
{
    /**
     * spanName returns an appropriate span name based on the request,
     * usually the HTTP method is enough (e.g GET or POST).
     */
    protected function spanName(Request $request): string
    {
        return $request->getMethod();
    }

    /**
     * {@inhertidoc}
     */
    public function request(Request $request, TraceContext $context, SpanCustomizer $span): void
    {
        $span->setName($this->spanName($request));
        $span->tag(Tags\HTTP_METHOD, $request->getMethod());
        $span->tag(Tags\HTTP_PATH, $request->getPath() ?: "/");
    }

    /**
     * {@inhertidoc}
     */
    public function response(Response $response, TraceContext $context, SpanCustomizer $span): void
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode > 299) {
            $span->tag(Tags\HTTP_STATUS_CODE, (string) $statusCode);
        }

        if ($statusCode > 399) {
            $span->tag(Tags\ERROR, (string) $statusCode);
        }
    }
}
