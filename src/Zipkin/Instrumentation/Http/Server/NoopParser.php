<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server;

use Zipkin\SpanCustomizer;
use Zipkin\Propagation\TraceContext;

/**
 * NoopParser is a noop implementation of a parser that parses nothing as it does not know anything about the request.
 * It is responsibility of the library instrumentation to provide a default parser as this should never be used in
 * production.
 */
final class NoopParser implements Parser
{
    public function spanName($request): string
    {
        return '';
    }

    public function request($request, TraceContext $context, SpanCustomizer $span): void
    {
    }

    public function response($response, TraceContext $context, SpanCustomizer $span): void
    {
    }
}
