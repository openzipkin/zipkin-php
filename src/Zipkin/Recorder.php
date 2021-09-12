<?php

declare(strict_types=1);

namespace Zipkin;

use Zipkin\Reporters\Noop;
use Zipkin\Recording\SpanMap;
use Zipkin\Propagation\TraceContext;
use Throwable;

class Recorder
{
    private Endpoint $endpoint;

    private SpanMap $spanMap;

    private Reporter $reporter;

    private bool $noop;

    public function __construct(
        Endpoint $endpoint,
        Reporter $reporter,
        bool $isNoop = false
    ) {
        $this->endpoint = $endpoint;
        $this->reporter = $reporter;
        $this->noop = $isNoop;
        $this->spanMap = new SpanMap();
    }

    public static function createAsNoop(): self
    {
        return new self(Endpoint::createAsEmpty(), new Noop(), true);
    }

    public function getTimestamp(TraceContext $context): ?int
    {
        $span = $this->spanMap->get($context);

        if ($span !== null && $span->getTimestamp() !==  null) {
            return $span->getTimestamp();
        }

        return null;
    }

    public function start(TraceContext $context, int $timestamp): void
    {
        $span = $this->spanMap->getOrCreate($context, $this->endpoint);
        $span->start($timestamp);
    }

    public function setName(TraceContext $context, string $name): void
    {
        if ($this->noop) {
            return;
        }

        $span = $this->spanMap->getOrCreate($context, $this->endpoint);
        $span->setName($name);
    }

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

    public function setRemoteEndpoint(TraceContext $context, Endpoint $remoteEndpoint): void
    {
        if ($this->noop) {
            return;
        }

        $span = $this->spanMap->getOrCreate($context, $this->endpoint);
        $span->setRemoteEndpoint($remoteEndpoint);
    }

    public function finish(TraceContext $context, int $finishTimestamp): void
    {
        $span = $this->spanMap->get($context);

        if ($span !== null) {
            $span->finish($finishTimestamp);
        }
    }

    public function abandon(TraceContext $context): void
    {
        $this->spanMap->remove($context);
    }

    public function flush(TraceContext $context): void
    {
        $span = $this->spanMap->remove($context);

        if ($span !== null && !$this->noop) {
            $span->finish();
            $this->reporter->report([$span]);
        }
    }

    public function flushAll(): void
    {
        $this->reporter->report($this->spanMap->removeAll());
    }
}
