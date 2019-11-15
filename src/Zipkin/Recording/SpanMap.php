<?php

declare(strict_types=1);

namespace Zipkin\Recording;

use Zipkin\Endpoint;
use Zipkin\Propagation\TraceContext;

final class SpanMap
{
    /**
     * @var Span[]
     */
    private $map = [];

    /**
     * @return SpanMap
     */
    public static function create()
    {
        return new self();
    }

    /**
     * @param TraceContext $context
     * @return Span|null
     */
    public function get(TraceContext $context): ?Span
    {
        $contextHash = $this->getHash($context);

        if (!\array_key_exists($contextHash, $this->map)) {
            return null;
        }

        return $this->map[$contextHash];
    }

    /**
     * @param TraceContext $context
     * @param Endpoint $endpoint
     * @return Span
     */
    public function getOrCreate(TraceContext $context, Endpoint $endpoint): Span
    {
        $contextHash = $this->getHash($context);

        if (!\array_key_exists($contextHash, $this->map)) {
            $this->map[$contextHash] = Span::createFromContext($context, $endpoint);
        }

        return $this->map[$contextHash];
    }

    /**
     * @param TraceContext $context
     * @return Span|null
     */
    public function remove(TraceContext $context): ?Span
    {
        $contextHash = $this->getHash($context);

        if (!\array_key_exists($contextHash, $this->map)) {
            return null;
        }

        $span = $this->map[$contextHash];

        unset($this->map[$contextHash]);

        return $span;
    }

    /**
     * @return Span[]
     */
    public function removeAll(): array
    {
        $spans = $this->map;

        $this->map = [];

        return \array_values($spans);
    }

    /**
     * @param TraceContext $context
     * @return int
     */
    private function getHash(TraceContext $context): int
    {
        return \crc32($context->getSpanId() . $context->getTraceId());
    }
}
