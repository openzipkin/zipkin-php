<?php

namespace Zipkin;

use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\Sampler;

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
    private $traceId128bits;

    /**
     * @var Recorder
     */
    private $recorder;

    /**
     * @param Endpoint $localEndpoint
     * @param Reporter $reporter
     * @param Sampler $sampler
     * @param bool $traceId128bits
     * @param bool $isNoop
     */
    public function __construct(
        Endpoint $localEndpoint,
        Reporter $reporter,
        Sampler $sampler,
        $traceId128bits,
        $isNoop
    ) {
        $this->recorder = new Recorder($localEndpoint, $reporter, $isNoop);
        $this->sampler = $sampler;
        $this->traceId128bits = $traceId128bits;
        $this->isNoop = $isNoop;
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
    public function newTrace(SamplingFlags $samplingFlags = null)
    {
        if ($samplingFlags === null) {
            $samplingFlags = DefaultSamplingFlags::createAsEmpty();
        }

        return $this->ensureSampled($this->nextContext($samplingFlags));
    }

    /**
     * Creates a new span within an existing trace. If there is no existing trace, use {@link
     * #newTrace()} instead.
     *
     * @param TraceContext $parent
     * @return Span
     */
    public function newChild(TraceContext $parent)
    {
        if ($parent->isSampled()) {
            return $this->ensureSampled($this->nextContext($parent));
        }

        return NoopSpan::create($parent);
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
    public function joinSpan(TraceContext $context)
    {
        return $this->toSpan($context);
    }

    /**
     * Calling this will flush any pending spans to the transport on the current thread.
     *
     * Make sure this method is called after the request is finished.
     * As an implementor, a good idea would be to use an asynchronous message bus
     * or use the call to fastcgi_finish_request in order to not to delay the end
     * of the request to the client.
     *
     * @see fastcgi_finish_request()
     * @see https://www.google.com/search?q=message+bus+php
     */
    public function flush()
    {
        $this->recorder->flushAll();
    }

    /**
     * @param SamplingFlags|TraceContext $contextOrFlags
     * @return TraceContext
     */
    private function nextContext(SamplingFlags $contextOrFlags)
    {
        if ($contextOrFlags instanceof TraceContext) {
            $context = TraceContext::createFromParent($contextOrFlags);
        } else {
            $context = TraceContext::createAsRoot($contextOrFlags);
            //set trace id 128bits if flag is true after create a root trace context
            $context->setTraceId128bits($this->traceId128bits);
        }

        if ($context->isSampled() === null) {
            $context = $context->withSampled($this->sampler->isSampled($context->getTraceId()));
        }

        return $context;
    }

    /**
     * @param TraceContext $context
     * @return Span
     */
    private function ensureSampled(TraceContext $context)
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
    private function toSpan(TraceContext $context)
    {
        if (!$this->isNoop && $context->isSampled()) {
            return RealSpan::create($context, $this->recorder);
        }

        return NoopSpan::create($context);
    }
}
