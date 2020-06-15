<?php

declare(strict_types=1);

namespace Zipkin;

use Throwable;
use Zipkin\Sampler;
use RuntimeException;
use Zipkin\SpanCustomizer;
use BadMethodCallException;
use Zipkin\SpanCustomizerShield;
use Zipkin\Propagation\TraceContext;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\Propagation\CurrentTraceContext;
use Zipkin\Propagation\DefaultSamplingFlags;
use function Zipkin\SpanName\generateSpanName;

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
     * @var bool
     */
    private $supportsJoin;

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
        bool $isNoop,
        bool $supportsJoin = true
    ) {
        $this->recorder = new Recorder($localEndpoint, $reporter, $isNoop);
        $this->sampler = $sampler;
        $this->usesTraceId128bits = $usesTraceId128bits;
        $this->currentTraceContext = $currentTraceContext;
        $this->isNoop = $isNoop;
        $this->supportsJoin = $supportsJoin;
    }

    /**
     * Creates a new trace. If there is an existing trace, use {@link #newChild(TraceContext)}
     * instead.
     *
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
     *   return $this->tracer->newTrace($flags);
     * }
     * }</pre>
     *
     * @param SamplingFlags $samplingFlags
     * @return Span
     */
    public function newTrace(SamplingFlags $samplingFlags = null): Span
    {
        if ($samplingFlags === null) {
            $samplingFlags = DefaultSamplingFlags::createAsEmpty();
        }

        return $this->ensureSampled($this->newRootContext($samplingFlags));
    }

    /**
     * Creates a new span within an existing trace. If there is no existing trace, use {@link
     * #newTrace()} instead.
     *
     * @param TraceContext $parent
     * @return Span
     * @throws \RuntimeException
     */
    public function newChild(TraceContext $parent): Span
    {
        return $this->nextSpan($parent);
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
        if (!$this->supportsJoin) {
            return $this->newChild($context);
        }

        return $this->toSpan($context->withShared(true));
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
    public function openScope(Span $span = null): callable
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
    public function flush(): void
    {
        $this->recorder->flushAll();
    }

    /**
     * This creates a new span based on parameters extracted from an incoming request. This will
     * always result in a new span. If no trace identifiers were extracted, a span will be created
     * based on the implicit context in the same manner as {@link #nextSpan()}.
     *
     * <p>Ex.
     * <pre>{@code
     * $extracted = $extractor->extract($headers);
     * $span = $tracer->nextSpan($extracted);
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
     * @param SamplingFlags|TraceContext $contextOrFlags
     * @return Span
     * @throws \RuntimeException
     */
    public function nextSpan(SamplingFlags $contextOrFlags = null): Span
    {
        if ($contextOrFlags === null) {
            $parent = $this->currentTraceContext->getContext();
            return $parent === null ? $this->newTrace() : $this->newChild($parent);
        }

        if ($contextOrFlags instanceof TraceContext) {
            return $this->toSpan(TraceContext::createFromParent($contextOrFlags));
        }

        if ($contextOrFlags instanceof SamplingFlags) {
            $implicitParent = $this->currentTraceContext->getContext();
            if ($implicitParent === null) {
                return $this->toSpan($this->newRootContext($contextOrFlags));
            }
        }

        throw new RuntimeException('Context or flags for next span is invalid.');
    }

    /**
     * This creates a new span based on a function call and the passed arguments. There are many
     * situations where instrumentation can be a very manual process and this convenience method
     * abstracts all that complexity. This method is mainly for local tracing (e.g. complex marshaling
     * worth to be measured as it takes a good chunk of the request time) or a legacy client call
     * using curl that can't easily be refactored to use a modern http client.
     *
     * For example:
     *
     * <p>Ex.
     * <pre>{@code
     * $ch = curl_init();
     * curl_setopt($ch, CURLOPT_URL, $url);
     * curl_setopt($ch, CURLOPT_HEADER, TRUE);
     * curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body
     * curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
     * $response = $tracer->inSpan('curl_exec', [$ch], 'api_call', function($span, $args) {
     *     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
     *     $span->setTag('http.status_code', $httpCode);
     *     if ($httpCode/100 >= 5) {
     *         $span->setTag('error', $httpCode);
     *     }
     * );
     * curl_close($ch);
     * }</pre>
     *
     * <p><em>Note:</em> The fourth parameter is a arguments parser which allows the user to
     * set tags based on the args passed to the function. Arguments' copy can be expensive so
     * user should be careful about passing this function. The fifth parameter is an result parser
     * which allows the user to set tags based on the output of the function and a exception thrown
     * by the callable. By default, this function will tag with an error if the callable throws an
     * exception.
     *
     * <p><em>Note:</em> argsParser and resultParser are not executed in a natural order mainly
     * because some functions (like curl) require that the first argument to be passed to obtain
     * certain information about the output. In any case this should not be a problem unless it is
     * desired to set annotations on the span which we discourage you because annotations are
     * suppose to be meaningful events in the span lifecycle which one can't control in this black
     * box approach.
     *
     * @param callable $fn
     * @param array $args
     * @param string|null $name the name of the span
     * @param callable|null $argsParser with signature function (SpanCustomizer $span, array $args = []): void
     * @param callable|null $resultParser with signature
     * function (SpanCustomizer $span, $output = null, ?Throwable $e): void
     * @return mixed
     */
    public function inSpan(
        callable $fn,
        array $args = [],
        ?string $name = null,
        ?callable $argsParser = null,
        ?callable $resultParser = null
    ) {
        $span = $this->nextSpan();
        $span->setName($name ?: generateSpanName($fn));

        $spanCustomizer = new SpanCustomizerShield($span);
        $resultParser = $resultParser ?? [self::class, 'defaultResultParser'];

        $span->start();
        try {
            $result = \call_user_func_array($fn, $args);
            ($resultParser)($spanCustomizer, $result, null);
            return $result;
        } catch (Throwable $e) {
            ($resultParser)($spanCustomizer, null, $e);
            throw $e;
        } finally {
            if ($argsParser !== null) {
                $argsParser($spanCustomizer, $args);
            }
            $span->finish();
        }
    }
    
    private static function defaultResultParser(SpanCustomizer $spanCustomizer, $result, ?Throwable $e): void
    {
        if ($e != null) {
            $spanCustomizer->tag(Tags\ERROR, $e->getMessage());
        }
    }

    /**
     * @param SamplingFlags|TraceContext $contextOrFlags
     * @return TraceContext
     */
    private function newRootContext(SamplingFlags $contextOrFlags): TraceContext
    {
        $context = TraceContext::createAsRoot($contextOrFlags, $this->usesTraceId128bits);

        if ($context->isSampled() === null) {
            $context = $context->withSampled($this->sampler->isSampled($context->getTraceId()));
        }

        return $context;
    }

    /**
     * @param TraceContext $context
     * @return Span
     */
    private function ensureSampled(TraceContext $context): Span
    {
        if ($context->isSampled() === null) {
            $context = $context->withSampled($this->sampler->isSampled($context->getTraceId()));
        }

        return $this->toSpan($context);
    }

    /**
     * Converts the context as-is to a Span object
     *
     * @param TraceContext $context
     * @return Span
     */
    private function toSpan(TraceContext $context): Span
    {
        if (!$this->isNoop && $context->isSampled()) {
            return new RealSpan($context, $this->recorder);
        }

        return new NoopSpan($context);
    }
}
