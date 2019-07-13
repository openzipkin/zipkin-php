<?php

declare(strict_types=1);

namespace Zipkin;

use RuntimeException;
use Zipkin\Propagation\CurrentTraceContext;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\Propagation\TraceContext;
use Zipkin\Sampler;
use InvalidArgumentException;

final class Tracer
{
    /**
     * @var Sampler
     */
    private $sampler;

    /**
     * @var bool
     */
    private $isNoop;

    /**
     * @var bool
     */
    private $usesTraceId128bits;

    /**
     * @var Recorder
     */
    private $recorder;

    /**
     * @var CurrentTraceContext
     */
    private $currentTraceContext;

    /**
     * @param Endpoint $localEndpoint
     * @param Reporter $reporter
     * @param Sampler $sampler
     * @param bool $usesTraceId128bits
     * @param CurrentTraceContext $currentTraceContext
     * @param bool $isNoop
     */
    public function __construct(
        Endpoint $localEndpoint,
        Reporter $reporter,
        Sampler $sampler,
        bool $usesTraceId128bits,
        CurrentTraceContext $currentTraceContext,
        bool $isNoop
    ) {
        $this->recorder = new Recorder($localEndpoint, $reporter, $isNoop);
        $this->sampler = $sampler;
        $this->usesTraceId128bits = $usesTraceId128bits;
        $this->currentTraceContext = $currentTraceContext;
        $this->isNoop = $isNoop;
    }

    /**
     * Creates a new span. If there is an existing parent span, pass it as
     * "parent" in the options.
     *
     * For example, to sample all requests for a specific url:
     * <pre>{@code
     * function newTrace(Request $request) {
     *   $uri = $request->getUri();
     *   $flags = SamplingFlags::createAsEmpty();
     *   if (strpos('/experimental', $uri) !== false) {
     *     $flags = SamplingFlags::createAsSampled();
     *   } else if (strpos('/static', $uri) !== false) {
     *     $flags = SamplingFlags::createAsNotSampled();
     *   }
     *   return $this->tracer->startSpan('request', ['parent' => $flags]);
     * }
     * }</pre>
     */
    public function startSpan(string $name, array $options = []): Span
    {
        $parent = $options['parent'] ?? null;
        if (!($parent instanceof TraceContext)) {
            if ($parent === null || $parent instanceof SamplingFlags) {
                $parent = $this->newRootContext($parent);
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Invalid type for parent: null, %s or %s expected',
                    SamplingFlags::class,
                    TraceContext::class
                ));
            }
        }

        $span = $this->toSpan($parent);

        $defaultTags = $options['tags'] ?? [];
        foreach ($defaultTags as $key => $value) {
            $span->tag($key, (string) $value);
        }

        if (array_key_exists('remote_endpoint', $options)) {
            $span->setRemoteEndpoint($options['remote_endpoint']);
        }

        if (array_key_exists('kind', $options)) {
            $span->setKind($options['kind']);
        }
        
        return $span->setName($name)->start($options['start_time'] ?? null);
    }

    /**
     * This creates a new span based on parameters extracted from an incoming request. This will
     * always result in a new span. If no trace identifiers were extracted, a span will be created
     * based on the implicit context in the same manner as {@link #nextSpan()}.
     *
     * <p>Ex.
     * <pre>{@code
     * $extracted = $extractor->extract($headers);
     * $span = $tracer->startNextSpan($extracted);
     * }</pre>
     *
     * <p><em>Note:</em> Unlike {@link #joinSpan(TraceContext)}, this does not attempt to re-use
     * extracted span IDs. This means the extracted context (if any) is the parent of the span
     * returned.
     *
     * <p><em>Note:</em> If a context could be extracted from the input, that trace is resumed, not
     * whatever the {@link #currentSpan()} was. Make sure you re-apply {@link #withSpanInScope(Span)}
     * so that data is written to the correct trace.
     *
     * @throws RuntimeException
     */
    public function startNextSpan(string $name, array $options = []): Span
    {
        $parent = $this->currentTraceContext->getContext();

        if ($parent === null) {
            return $this->startSpan($name, $options);
        }

        if ($parent instanceof TraceContext) {
            return $this->startSpan($name, ['parent' => TraceContext::createFromParent($parent)] + $options);
        }

        throw new RuntimeException('Context or flags for next span is invalid.');
    }

    /**
     * Joining is re-using the same trace and span ids extracted from an incoming request. Here, we
     * ensure a sampling decision has been made. If the span passed sampling, we assume this is a
     * shared span, one where the caller and the current tracer report to the same span IDs. If no
     * sampling decision occurred yet, we have exclusive access to this span ID.
     *
     * <p>Here's an example of conditionally joining a span, depending on if a trace context was
     * extracted from an incoming request.
     *
     * <pre>{@code
     * $contextOrFlags = $extractor->extract($request->headers);
     * span = ($contextOrFlags instanceof TraceContext)
     *          ? $tracer->joinSpan($contextOrFlags)
     *          : $tracer->newTrace($contextOrFlags);
     * }</pre>
     *
     * @see Propagation
     * @see Extractor#extract(Object)
     *
     * @param TraceContext $context
     * @return Span
     */
    public function joinSpan(TraceContext $context): Span
    {
        return $this->toSpan($context);
    }

    /**
     * Makes the given span the "current span" and returns the closer that exits that scope.
     * The span provided will be returned by {@link #currentSpan()} until the return value is closed.
     *
     * <p>Note: While downstream code might affect the span, calling this method, and calling the closer on
     * the result have no effect on the input. For example, calling closer on the result does not
     * finish the span. Not only is it safe to call the closer, you must call the closer to end the scope, or
     * risk leaking resources associated with the scope.
     *
     * @param Span $span to place into scope or null to clear the scope
     *
     * @return callable The scope closer
     */
    public function openScope(?Span $span = null): callable
    {
        return $this->currentTraceContext->createScopeAndRetrieveItsCloser(
            $span === null ? null : $span->getContext()
        );
    }

    /**
     * Returns the current span in scope or null if there isn't one.
     *
     * @return Span|null
     */
    public function getCurrentSpan(): ?Span
    {
        $currentContext = $this->currentTraceContext->getContext();
        return $currentContext === null ? null : $this->toSpan($currentContext);
    }

    /**
     * Calling this will flush any pending spans to the transport.
     *
     * Make sure this method is called after the request is finished.
     *
     * @see register_shutdown_function()
     * @see fastcgi_finish_request()
     */
    public function flush()
    {
        $this->recorder->flushAll();
    }

    /**
     * @param SamplingFlags|TraceContext $contextOrFlags
     * @return TraceContext
     */
    private function newRootContext(?SamplingFlags $contextOrFlags = null): TraceContext
    {
        $context = TraceContext::createAsRoot($contextOrFlags, $this->usesTraceId128bits);
        return $this->ensureSampled($context);
    }

    /**
     * @param TraceContext $context
     */
    private function ensureSampled(TraceContext $context): TraceContext
    {
        if ($context->isSampled() === null) {
            return $context->withSampled($this->sampler->isSampled($context->getTraceId()));
        }

        return $context;
    }

    /**
     * Converts the context as-is to a Span object
     */
    private function toSpan(TraceContext $context): Span
    {
        if (!$this->isNoop && $context->isSampled()) {
            return RealSpan::create($context, $this->recorder);
        }

        return NoopSpan::create($context);
    }
}
