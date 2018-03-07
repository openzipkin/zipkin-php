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

    /**
     * {@inheritdoc}
     */
    public function incrementMessages()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function incrementMessagesDropped($cause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function incrementSpanBytes($quantity)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function incrementMessageBytes($quantity)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function updateQueuedSpans($update)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function updateQueuedBytes($update)
    {
    }
}
