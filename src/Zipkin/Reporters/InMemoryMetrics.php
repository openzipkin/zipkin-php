<?php

namespace Zipkin\Reporters;

final class InMemoryMetrics implements Metrics
{
    /**
     * @var int
     */
    private $spansCount = 0;

    /**
     * @var int
     */
    private $spanBytesCount = 0;

    /**
     * @var int
     */
    private $spansDroppedCount = 0;

    /**
     * @var int
     */
    private $messagesCount = 0;

    /**
     * @var int
     */
    private $messagesDroppedCount = 0;

    /**
     * @var int
     */
    private $messageBytes = 0;

    /**
     * @var int
     */
    private $queuedSpans = 0;

    /**
     * @var int
     */
    private $queuedBytes = 0;

    /**
     * {@inheritdoc}
     */
    public function incrementSpans($quantity)
    {
        $this->spansCount += $quantity;
    }

    public function getSpans()
    {
        return $this->spansCount;
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

    /**
     * {@inheritdoc}
     */
    public function incrementSpanBytes($quantity)
    {
        $this->spanBytesCount += $quantity;
    }

    public function getSpanBytes()
    {
        return $this->spanBytesCount;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementMessages()
    {
        $this->messagesCount++;
    }

    public function getMessages()
    {
        return $this->messagesCount;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementMessagesDropped($cause)
    {
        $this->messagesDroppedCount++;
    }

    public function getMessagesDropped()
    {
        return $this->messagesDroppedCount;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementMessageBytes($quantity)
    {
        $this->messageBytes += $quantity;
    }

    public function getMessageBytes()
    {
        return $this->messageBytes;
    }

    /**
     * {@inheritdoc}
     */
    public function updateQueuedSpans($update)
    {
        $this->queuedSpans = $update;
    }

    public function getQueuedSpans()
    {
        return $this->queuedSpans;
    }

    /**
     * {@inheritdoc}
     */
    public function updateQueuedBytes($update)
    {
        $this->queuedBytes = $update;
    }

    public function getQueuedBytes()
    {
        return $this->queuedBytes;
    }
}
