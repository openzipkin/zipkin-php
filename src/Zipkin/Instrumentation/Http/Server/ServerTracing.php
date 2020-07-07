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
    private $nextSpanHandler;

    /**
     * @param Tracing $tracing
     * @param Parser $parser HTTP server parser to obtain meaningful information from
     * request and response and tag the span accordingly.
     * @param callable $requestSampler function that decides to sample or not an unsampled
     * request. The signature is:
     *
     * <pre>
     * function (RequestInterface $request): ?bool {}
     * </pre>
     */
    public function __construct(
        Tracing $tracing,
        Parser $parser = null,
        callable $requestSampler = null
    ) {
        $this->tracing = $tracing;
        $this->parser = $parser ?? new DefaultParser;
        $this->nextSpanHandler = self::buildNextSpanHandler($tracing->getTracer(), $requestSampler);
    }

    public function getTracing(): Tracing
    {
        return $this->tracing;
    }

    public function getParser(): Parser
    {
        return $this->parser;
    }

    public function getNextSpanHandler(): callable
    {
        return $this->nextSpanHandler;
    }

    private static function buildNextSpanHandler(Tracer $tracer, ?callable $requestSampler): callable
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
