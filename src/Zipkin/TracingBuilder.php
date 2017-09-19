<?php

namespace Zipkin;

use Psr\Log\NullLogger;
use src\Zipkin\Reporters\Logging;

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
    private $traceId128bits = false;

    public static function create()
    {
        return new self();
    }

    /**
     * Controls the name of the service being traced, while still using a default site-local IP.
     * This is an alternative to {@link #localEndpoint(Endpoint)}.
     *
     * @param $localServiceName name of the service being traced. Defaults to "unknown".
     */
    public function havingLocalServiceName($localServiceName)
    {
        $this->localServiceName = $localServiceName;
    }

    /**
     * @param localEndpoint Endpoint of the local service being traced. Defaults to site local.
     */
    public function havingLocalEndpoint(Endpoint $endpoint)
    {
        $this->localEndpoint = $endpoint;
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
     */
    public function havingReporter(Reporter $reporter)
    {
        $this->reporter = $reporter;
    }

    /**
     * Sampler is responsible for deciding if a particular trace should be "sampled", i.e. whether
     * the overhead of tracing will occur and/or if a trace will be reported to Zipkin.
     */
    public function havingSampler(Sampler $sampler)
    {
        $this->sampler = $sampler;
    }

    /** When true, new root spans will have 128-bit trace IDs. Defaults to false (64-bit) */
    public function havingTraceId128bits($traceId128Bits)
    {
        $this->traceId128bits = $traceId128Bits;
    }

    public function build()
    {
        if ($this->localEndpoint === null) {
            $this->localEndpoint = Endpoint::createFromGlobals();
            if ($this->localServiceName !== null) {
                $this->localEndpoint->withServiceName($this->localServiceName);
            }
        }

        if ($this->reporter === null) {
            $this->reporter = new Logging(new NullLogger());
        }

        return new DefaultTracing(
            $this->localEndpoint,
            $this->reporter,
            $this->sampler,
            $this->traceId128bits
        );
    }
}