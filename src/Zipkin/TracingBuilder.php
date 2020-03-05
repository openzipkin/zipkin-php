<?php

declare(strict_types=1);

namespace Zipkin;

use Psr\Log\NullLogger;
use Zipkin\Propagation\CurrentTraceContext;
use Zipkin\Reporters\Log;
use Zipkin\Samplers\BinarySampler;

class TracingBuilder
{
    /**
     * @var string|null
     */
    private $localServiceName;

    /**
     * @var Endpoint|null
     */
    private $localEndpoint;

    /**
     * @var Reporter|null
     */
    private $reporter;

    /**
     * @var Sampler|null
     */
    private $sampler;

    /**
     * @var bool
     */
    private $usesTraceId128bits = false;

    /**
     * @var CurrentTraceContext|null
     */
    private $currentTraceContext;

    /**
     * @var bool
     */
    private $isNoop = false;

    public static function create(): self
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
    public function havingLocalServiceName(string $localServiceName): self
    {
        $this->localServiceName = $localServiceName;
        return $this;
    }

    /**
     * @param Endpoint $endpoint Endpoint of the local service being traced. Defaults to site local.
     * @return $this
     */
    public function havingLocalEndpoint(Endpoint $endpoint): self
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
    public function havingReporter(Reporter $reporter): self
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
    public function havingSampler(Sampler $sampler): self
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
    public function havingTraceId128bits(bool $usesTraceId128bits): self
    {
        $this->usesTraceId128bits = $usesTraceId128bits;
        return $this;
    }

    /**
     * @param CurrentTraceContext $currentTraceContext
     * @return $this
     */
    public function havingCurrentTraceContext(CurrentTraceContext $currentTraceContext): self
    {
        $this->currentTraceContext = $currentTraceContext;
        return $this;
    }

    /**
     * @param bool $isNoop
     * @return $this
     */
    public function beingNoop(bool $isNoop = true): self
    {
        $this->isNoop = $isNoop;
        return $this;
    }

    /**
     * @return DefaultTracing
     */
    public function build(): Tracing
    {
        $localEndpoint = $this->localEndpoint;
        if ($this->localEndpoint === null) {
            $localEndpoint = Endpoint::createFromGlobals();
            if ($this->localServiceName !== null) {
                $localEndpoint = $localEndpoint->withServiceName($this->localServiceName);
            }
        }

        $reporter = $this->reporter ?: new Log(new NullLogger());
        $sampler = $this->sampler ?: BinarySampler::createAsNeverSample();
        $currentTraceContext = $this->currentTraceContext ?: new CurrentTraceContext;

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
