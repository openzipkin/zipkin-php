<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Throwable;
use Zipkin\SpanCustomizer;
use Zipkin\Propagation\TraceContext;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Handler
{
    public function spanName(RequestInterface $request): string;

    public function requestSampler(RequestInterface $request): ?bool;

    public function parseRequest(RequestInterface $request, TraceContext $context, SpanCustomizer $span): void;

    public function parseResponse(ResponseInterface $response, TraceContext $context, SpanCustomizer $span): void;

    public function parseError(Throwable $e, TraceContext $context, SpanCustomizer $span): void;
}
