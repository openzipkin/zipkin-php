<?php

namespace Zipkin\Reporters;

final class InMemoryMetrics implements Metrics
{
    /**
     * @var int
     */
    private $incrementedSpans = 0;

    /**
     * @var int
     */
    private $spansDroppedCount = 0;

    /**
     * {@inheritdoc}
     */
    public function incrementSpans($quantity)
    {
        $this->incrementedSpans += $quantity;
    }

    public function getSpans()
    {
        return $this->incrementedSpans;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementSpansDropped($quantity)
    {
        $this->spansDroppedCount += $quantity;
    }

    public function getSpansDropped()
    {
        return $this->spansDroppedCount;
    }
}
