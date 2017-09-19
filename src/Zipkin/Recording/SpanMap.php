<?php

namespace Zipkin\Recording;

use Zipkin\Endpoint;
use Zipkin\TraceContext;

final class SpanMap
{
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
    public function get(TraceContext $context)
    {
        $contextHash = $this->getHash($context);

        if (!array_key_exists($contextHash, $this->map)) {
            return null;
        }

        return $this->map[$contextHash];
    }

    /**
     * @param TraceContext $context
     * @param Endpoint $endpoint
     * @return Span
     */
    public function getOrCreate(TraceContext $context, Endpoint $endpoint)
    {
        $contextHash = $this->getHash($context);

        if (!array_key_exists($contextHash, $this->map)) {
            $this->map[$contextHash] = Span::createFromContext($context, $endpoint);
        }

        return $this->map[$contextHash];

    }

    /**
     * @param $context
     * @return Span|null
     */
    public function remove($context)
    {
        $contextHash = $this->getHash($context);

        if (!array_key_exists($contextHash, $this->map)) {
            return null;
        }

        $span = $this->map[$contextHash];

        unset($this->map[$contextHash]);

        return $span;
    }

    /**
     * @return \Generator|Span[]
     */
    public function removeAll()
    {
        foreach ($this->map as $span) {
            yield $span;
        }

        $this->map = [];
    }

    /**
     * @param TraceContext $context
     * @return string
     */
    private function getHash(TraceContext $context)
    {
        return spl_object_hash($context);
    }
}