<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server\Psr15;

use Zipkin\Tags;
use Zipkin\SpanCustomizer;
use Zipkin\Propagation\TraceContext;
use Zipkin\Instrumentation\Http\Server\Parser;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * DefaultParser contains the basic logic for turning request/response information
 * into span name and tags. Implementors can use this as a base parser to reduce
 * boilerplate.
 */
class DefaultParser implements Parser
{
    public function spanName($request): string
    {
        self::assertRequestType($request);
        return $request->getMethod();
    }

    public function request($request, TraceContext $context, SpanCustomizer $span): void
    {
        self::assertRequestType($request);
        $span->tag(Tags\HTTP_METHOD, $request->getMethod());
        $span->tag(Tags\HTTP_PATH, $request->getUri()->getPath() ?: "/");
    }

    public function response($response, TraceContext $context, SpanCustomizer $span): void
    {
        self::assertResponseType($response);
        $span->tag(Tags\HTTP_STATUS_CODE, (string) $response->getStatusCode());
        if ($response->getStatusCode() > 399) {
            $span->tag(Tags\ERROR, (string) $response->getStatusCode());
        }
    }

    // Makes sure the type for request object is correct.
    private static function assertRequestType(ServerRequestInterface $request): void
    {
    }

    // Makes sure the type for response object is correct.
    private static function assertResponseType(ResponseInterface $response): void
    {
    }
}
