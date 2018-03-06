<?php

namespace Zipkin\Reporters;

final class NoopMetrics implements Metrics
{
    /**
     * {@inheritdoc}
     */
    public function incrementSpans($quantity)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function incrementSpansDropped($quantity)
    {
    }
}
