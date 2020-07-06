<?php

declare(strict_types=1);

namespace Zipkin;

use Zipkin\Reporters\Noop;
use Zipkin\Recording\SpanMap;
use Zipkin\Propagation\TraceContext;
use Throwable;

class Recorder
{
    /**
     * @var Endpoint
     */
    private $endpoint;

    /**
     * @var SpanMap
     */
    private $spanMap;

    /**
     * @var Reporter
     */
    private $reporter;

    /**
     * @var bool
     */
    private $noop;

    /**
     * @param Endpoint $endpoint
     * @param Reporter $reporter
     * @param bool $isNoop
     */
    public function __construct(
        Endpoint $endpoint,
        Reporter $reporter,
        bool $isNoop = false
    ) {
        $this->endpoint = $endpoint;
        $this->reporter = $reporter;
        $this->noop = $isNoop;
        $this->spanMap = new SpanMap;
    }

    public static function createAsNoop(): self
    {
        return new self(Endpoint::createAsEmpty(), new Noop(), true);
    }

    /**
     * @param TraceContext $context
     * @return int|null
     */
    public function getTimestamp(TraceContext $context): ?int
    {
        $span = $this->spanMap->get($context);

        if ($span !== null && $span->getTimestamp() !==  null) {
            return $span->getTimestamp();
        }

        return null;
    }

    /**
     * @param TraceContext $context
     * @param int $timestamp
     * @return void
     */
    public function start(TraceContext $context, int $timestamp): void
    {
        $span = $this->spanMap->getOrCreate($context, $this->endpoint);
        $span->start($timestamp);
    }

    /**
     * @param TraceContext $context
     * @param string $name
     * @return void
     */
    public function setName(TraceContext $context, string $name): void
    {
        if ($this->noop) {
            return;
        }

        $span = $this->spanMap->getOrCreate($context, $this->endpoint);
        $span->setName($name);
    }

    /**
     * @param TraceContext $context
     * @param string $kind
     * @return void
     */
    public function setKind(TraceContext $context, string $kind): void
    {
        if ($this->noop) {
            return;
        }

        $span = $this->spanMap->getOrCreate($context, $this->endpoint);
        $span->setKind($kind);
    }

    /**
     * @param TraceContext $context
     * @param int $timestamp
     * @param string $value
     * @throws \InvalidArgumentException
     * @return void
     */
    public function annotate(TraceContext $context, int $timestamp, string $value): void
    {
        if ($this->noop) {
            return;
        }

        $span = $this->spanMap->getOrCreate($context, $this->endpoint);
        $span->annotate($timestamp, $value);
    }

    /**
     * @param TraceContext $context
     * @param string $key
     * @param string $value
     * @return void
     */
    public function tag(TraceContext $context, string $key, string $value): void
    {
        if ($this->noop) {
            return;
        }

        $span = $this->spanMap->getOrCreate($context, $this->endpoint);
        $span->tag($key, $value);
    }

    public function setError(TraceContext $context, Throwable $e): void
    {
        if ($this->noop) {
            return;
        }

        $span = $this->spanMap->getOrCreate($context, $this->endpoint);
        $span->setError($e);
    }

    /**
     * @param TraceContext $context
     * @param Endpoint $remoteEndpoint
     * @return void
     */
    public function setRemoteEndpoint(TraceContext $context, Endpoint $remoteEndpoint): void
    {
        if ($this->noop) {
            return;
        }

        $span = $this->spanMap->getOrCreate($context, $this->endpoint);
        $span->setRemoteEndpoint($remoteEndpoint);
    }

    /**
     * @param TraceContext $context
     * @param int $finishTimestamp
     * @return void
     */
    public function finish(TraceContext $context, int $finishTimestamp): void
    {
        $span = $this->spanMap->get($context);

        if ($span !== null) {
            $span->finish($finishTimestamp);
        }
    }

    /**
     * @param TraceContext $context
     * @return void
     */
    public function abandon(TraceContext $context): void
    {
        $this->spanMap->remove($context);
    }

    /**
     * @param TraceContext $context
     * @return void
     */
    public function flush(TraceContext $context): void
    {
        $span = $this->spanMap->remove($context);

        if ($span !== null && !$this->noop) {
            $span->finish();
            $this->reporter->report([$span]);
        }
    }

    /**
     * @return void
     */
    public function flushAll(): void
    {
        $this->reporter->report($this->spanMap->removeAll());
    }
}
