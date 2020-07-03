<?php

declare(strict_types=1);

namespace Zipkin\Recording;

use Zipkin\Propagation\TraceContext;
use Zipkin\Endpoint;

final class SpanMap
{
    /**
     * @var Span[]|array
     */
    private $map = [];

    public function get(TraceContext $context): ?Span
    {
        $contextHash = self::getHash($context);

        return $this->map[$contextHash] ?? null;
    }

    public function getOrCreate(TraceContext $context, Endpoint $endpoint): Span
    {
        $contextHash = self::getHash($context);

        if (!\array_key_exists($contextHash, $this->map)) {
            $this->map[$contextHash] = Span::createFromContext($context, $endpoint);
        }

        return $this->map[$contextHash];
    }

    public function remove(TraceContext $context): ?Span
    {
        $contextHash = self::getHash($context);

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

    private static function getHash(TraceContext $context): int
    {
        return \crc32($context->getSpanId() . $context->getTraceId());
    }
}
