<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Throwable;
use Zipkin\SpanCustomizer;
use Zipkin\Propagation\TraceContext;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Parser
{
    /**
     * spanName returns an appropiate span name based on the request,
     * usually the HTTP method is good enough.
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

    /**
     * error parses the exception when doing a HTTP call, usually it is good enough to tag
     * the throwable message but depending on the wrapping client, one might want to enrich
     * the error with meaningful information from the exception.
     */
    public function error(Throwable $e, TraceContext $context, SpanCustomizer $span): void;
}
