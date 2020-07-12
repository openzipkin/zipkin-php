<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client\Psr;

use Zipkin\Tags;
use Zipkin\SpanCustomizer;
use Zipkin\Propagation\TraceContext;
use Zipkin\Instrumentation\Http\Client\Parser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

class DefaultParser implements Parser
{
    public function spanName(/*RequestInterface */$request): string
    {
        self::assertRequestType($request);

        return $request->getMethod();
    }

    public function request(/*RequestInterface */$request, TraceContext $context, SpanCustomizer $span): void
    {
        self::assertRequestType($request);

        $span->tag(Tags\HTTP_METHOD, $request->getMethod());
        $span->tag(Tags\HTTP_PATH, $request->getUri()->getPath() ?: "/");
    }

    public function response(/*ResponseInterface */$response, TraceContext $context, SpanCustomizer $span): void
    {
        self::assertResponseType($response);

        $span->tag(Tags\HTTP_STATUS_CODE, (string) $response->getStatusCode());
        if ($response->getStatusCode() > 399) {
            $span->tag(Tags\ERROR, (string) $response->getStatusCode());
        }
    }

    private static function assertRequestType(RequestInterface $request): void
    {
    }

    private static function assertResponseType(ResponseInterface $response): void
    {
    }
}
