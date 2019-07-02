<?php

namespace Zipkin;

use Psr\Log\NullLogger;
use Zipkin\Propagation\CurrentTraceContext;
use Zipkin\Reporters\Log;
use Zipkin\Samplers\BinarySampler;

class TracingBuilder
{
    /**
     * @var string
     */
    private $localServiceName;

    /**
     * @var Endpoint
     */
    private $localEndpoint;

    /**
     * @var Reporter
     */
    private $reporter;

    /**
     * @var Sampler
     */
    private $sampler;

    /**
     * @var bool
     */
    private $usesTraceId128bits = false;

    /**
     * @var CurrentTraceContext
     */
    private $currentTraceContext;

    /**
     * @var bool
     */
    private $isNoop = false;

    public static function create()
    {
        return new self();
    }

    /**
     * Controls the name of the service being traced, while still using a default site-local IP.
     * This is an alternative to {@link #localEndpoint(Endpoint)}.
     *
     * @param string $localServiceName name of the service being traced. Defaults to "unknown".
     * @return $this
     */
    public function havingLocalServiceName($localServiceName)
    {
        $this->localServiceName = $localServiceName;
        return $this;
    }

    /**
     * @param Endpoint $endpoint Endpoint of the local service being traced. Defaults to site local.
     * @return $this
     */
    public function havingLocalEndpoint(Endpoint $endpoint)
    {
        $this->localEndpoint = $endpoint;
        return $this;
    }

    /**
     * Controls how spans are reported. Defaults to logging, but often an {@link AsyncReporter}
     * which batches spans before sending to Zipkin.
     *
     * The {@link AsyncReporter} includes a {@link Sender}, which is a driver for transports like
     * http, kafka and scribe.
     *
     * <p>For example, here's how to batch send spans via http:
     *
     * <pre>{@code
     * reporter = AsyncReporter.v2(URLConnectionSender.json("http://localhost:9411/api/v2/spans"));
     *
     * tracingBuilder.spanReporter(reporter);
     * }</pre>
     *
     * <p>See https://github.com/openzipkin/zipkin-reporter-java
     *
     * @param Reporter $reporter
     * @return $this
     */
    public function havingReporter(Reporter $reporter)
    {
        $this->reporter = $reporter;
        return $this;
    }

    /**
     * Sampler is responsible for deciding if a particular trace should be "sampled", i.e. whether
     * the overhead of tracing will occur and/or if a trace will be reported to Zipkin.
     *
     * @param Sampler $sampler
     * @return $this
     */
    public function havingSampler(Sampler $sampler)
    {
        $this->sampler = $sampler;
        return $this;
    }

    /**
     * When true, new root spans will have 128-bit trace IDs. Defaults to false (64-bit)
     *
     * @param bool $usesTraceId128bits
     * @return $this
     */
    public function havingTraceId128bits($usesTraceId128bits)
    {
        $this->usesTraceId128bits = $usesTraceId128bits;
        return $this;
    }

    /**
     * @param CurrentTraceContext $currentTraceContext
     * @return $this
     */
    public function havingCurrentTraceContext(CurrentTraceContext $currentTraceContext)
    {
        $this->currentTraceContext = $currentTraceContext;
        return $this;
    }

    /**
     * @param bool $isNoop
     * @return $this
     */
    public function beingNoop($isNoop = true)
    {
        $this->isNoop = $isNoop;
        return $this;
    }

    /**
     * @return DefaultTracing
     */
    public function build()
    {
        $localEndpoint = $this->localEndpoint;
        if ($localEndpoint === null) {
            $localEndpoint = Endpoint::createFromGlobals();
            if ($this->localServiceName !== null) {
                $localEndpoint = $localEndpoint->withServiceName($this->localServiceName);
            }
        }

        $reporter = ($this->reporter ?: new Log(new NullLogger()));

        $sampler = $this->sampler ?: BinarySampler::createAsNeverSample();

        $currentTraceContext = $this->currentTraceContext ?: CurrentTraceContext::create();

        return new DefaultTracing(
            $localEndpoint,
            $reporter,
            $sampler,
            $this->usesTraceId128bits,
            $currentTraceContext,
            $this->isNoop
        );
    }
}
