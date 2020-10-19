<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server;

use Zipkin\Tags;
use Zipkin\SpanCustomizer;
use Zipkin\Propagation\TraceContext;
use Zipkin\Instrumentation\Http\Server\HttpServerParser;

/**
 * DefaultParser contains the basic logic for turning request/response information
 * into span name and tags. Implementors can use this as a base parser to reduce
 * boilerplate.
 */
class DefaultHttpServerParser implements HttpServerParser
{
    /**
     * spanNameFromRequest returns an appropiate span name based on the request,
     * usually the HTTP method is enough (e.g GET or POST) but ideally
     * the verb+route is desired (e.g. GET /user/{user_id}).
     */
    protected function spanNameFromRequest(Request $request): string
    {
        return $request->getMethod()
            . ($request->getRoute() === null ? '' : ' ' . $request->getRoute());
    }

    /**
     * {@inhertidoc}
     */
    public function request(Request $request, TraceContext $context, SpanCustomizer $span): void
    {
        $span->setName($this->spanNameFromRequest($request));
        $span->tag(Tags\HTTP_METHOD, $request->getMethod());
        $span->tag(Tags\HTTP_PATH, $request->getPath() ?: '/');
    }

    protected function spanName(Request $request): string
    {
        return $request->getMethod()
            . ($request->getRoute() === null ? '' : ' ' . $request->getRoute());
    }

    /**
     * spanNameFromResponse returns an appropiate span name based on the response's request,
     * usually seeking for a better name than the HTTP method (e.g. GET /user/{user_id}).
     */
    protected function spanNameFromResponse(Response $response): ?string
    {
        if ($response->getRoute() === null || $response->getRequest() === null) {
            return null;
        }

        return $response->getRequest()->getMethod() . ' ' . $response->getRoute();
    }


    /**
     * {@inhertidoc}
     */
    public function response(Response $response, TraceContext $context, SpanCustomizer $span): void
    {
        $spanName = $this->spanNameFromResponse($response);
        if ($spanName !== null) {
            $span->setName($spanName);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode > 299) {
            $span->tag(Tags\HTTP_STATUS_CODE, (string) $statusCode);
        }

        if ($statusCode > 399) {
            $span->tag(Tags\ERROR, (string) $statusCode);
        }
    }
}
