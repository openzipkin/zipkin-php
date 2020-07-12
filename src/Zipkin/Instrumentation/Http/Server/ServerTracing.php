<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server;

use Zipkin\Tracing;
use Zipkin\Tracer;
use Zipkin\Span;
use Zipkin\Propagation\TraceContext;
use Zipkin\Propagation\SamplingFlags;

/**
 * Tracing includes all the elements needed to trace a HTTP server
 * middleware or request handler.
 */
class ServerTracing
{
    /**
     * @var Tracing
     */
    private $tracing;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var callable
     */
    private $nextSpanResolver;

    /**
     * @param Tracing $tracing
     * @param Parser $parser HTTP server parser to obtain meaningful information from
     * request and response and tag the span accordingly.
     * @param callable(mixed):?bool $requestSampler function that decides to sample or not an unsampled
     * request.
     */
    public function __construct(
        Tracing $tracing,
        Parser $parser = null,
        callable $requestSampler = null
    ) {
        $this->tracing = $tracing;
        $this->parser = $parser ?? new NoopParser;
        $this->nextSpanResolver = self::buildNextSpanResolver($tracing->getTracer(), $requestSampler);
    }

    public function getTracing(): Tracing
    {
        return $this->tracing;
    }

    /**
     * @return Parser the server parser for enriching span information based on the request
     */
    public function getParser(): Parser
    {
        return $this->parser;
    }

    /**
     * @return callable(TraceContext, $request): Span the next span handler which creates an appropriate span based
     * on the extracted context.
     */
    public function getNextSpanResolver(): callable
    {
        return $this->nextSpanResolver;
    }

    private static function buildNextSpanResolver(Tracer $tracer, ?callable $requestSampler): callable
    {
        return static function (SamplingFlags $extractedContext, $request) use ($tracer, $requestSampler): Span {
            if ($extractedContext instanceof TraceContext) {
                return $tracer->joinSpan($extractedContext);
            }

            if ($requestSampler === null) {
                return $tracer->nextSpan($extractedContext);
            }

            return $tracer->nextSpanWithSampler(
                $requestSampler,
                [$request],
                $extractedContext
            );
        };
    }
}
