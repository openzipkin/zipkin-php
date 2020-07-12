<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client;

use Zipkin\Tracing;
use Zipkin\Tracer;
use Zipkin\Span;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\Instrumentation\Http\Client\Parser;

/**
 * ClientTracing includes all the elements needed to instrument a
 * HTTP client.
 */
class ClientTracing
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
     * @param Parser $parser HTTP client parser to obtain meaningful information from
     * request and response and tag the span accordingly.
     * @param callable(mixed):?bool $requestSampler function that decides to sample or not an unsampled
     * request.
     * @param callable(mixed):?SamplingFlags $traceContextObtainer function that obtains the context from the
     * request. It is mostly used in event loop scenaries where the global scope can't be used.
     */
    public function __construct(
        Tracing $tracing,
        Parser $parser = null,
        callable $requestSampler = null,
        callable $traceContextObtainer = null
    ) {
        $this->tracing = $tracing;
        $this->parser = $parser ?? new NoopParser;
        $this->nextSpanResolver = self::buildNextSpanResolver(
            $tracing->getTracer(),
            $requestSampler,
            $traceContextObtainer
        );
    }

    public function getTracing(): Tracing
    {
        return $this->tracing;
    }

    public function getParser(): Parser
    {
        return $this->parser;
    }

    /**
     * @return callable(mixed):Span the next span handler which creates an appropriate span based on the current scope,
     * and the incoming request.
     */
    public function getNextSpanResolver(): callable
    {
        return $this->nextSpanResolver;
    }

    private static function buildNextSpanResolver(
        Tracer $tracer,
        ?callable $requestSampler,
        ?callable $traceContextObtainer
    ): callable {
        return static function ($request) use ($tracer, $requestSampler, $traceContextObtainer): Span {
            if ($traceContextObtainer !== null) {
                // in this case, the trace context is meant to be obtained from the request
                $traceContext = ($traceContextObtainer)($request);

                if ($requestSampler !== null) {
                    return $tracer->nextSpanWithSampler(
                        $requestSampler,
                        [$request],
                        $traceContext
                    );
                }
                return $tracer->nextSpan($traceContext);
            }

            if ($requestSampler !== null) {
                return $tracer->nextSpanWithSampler(
                    $requestSampler,
                    [$request]
                );
            }

            return $tracer->nextSpan();
        };
    }
}
