<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Zipkin\SpanCustomizer;
use Zipkin\Propagation\TraceContext;
use Throwable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Parser includes the methods to obtain meaningful span information
 * out of HTTP request/response elements.
 */
interface Parser
{
    /**
     * spanName returns an appropiate span name based on the request,
     * usually the HTTP method is good enough (e.g GET or POST).
     */
    public function spanName(RequestInterface $request): string;

    /**
     * request parses the incoming data related to a request in order to add
     * relevant information to the span under the SpanCustomizer interface.
     *
     * Basic data being tagged is HTTP method, HTTP path but other information
     * such as query parameters can be added to enrich the span information.
     */
    public function request(RequestInterface $request, TraceContext $context, SpanCustomizer $span): void;


    /**
     * response parses the response data in order to add relevant information
     * to the span under the SpanCustomizer interface.
     *
     * Basic data being tagged is HTTP status code but other information such
     * as any response header or more granular information based on the response
     * payload can be added.
     */
    public function response(ResponseInterface $response, TraceContext $context, SpanCustomizer $span): void;
}
