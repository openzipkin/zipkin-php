<?php

namespace ZipkinTests\Unit\Reporters;

use PHPUnit_Framework_TestCase;
use Zipkin\Reporters\InMemoryMetrics;

final class InMemoryMetricsTest extends PHPUnit_Framework_TestCase
{
    public function testMetricsSpansSuccess()
    {
        $metrics = new InMemoryMetrics();
        $metrics->incrementSpans(1);
        $metrics->incrementSpansDropped(2);
        $metrics->incrementSpanBytes(3);
        $metrics->incrementMessages();
        $metrics->incrementMessagesDropped(new \Exception);
        $metrics->incrementMessageBytes(4);
        $metrics->updateQueuedSpans(5);
        $metrics->updateQueuedBytes(6);
        $this->assertEquals(1, $metrics->getSpans());
        $this->assertEquals(2, $metrics->getSpansDropped());
        $this->assertEquals(3, $metrics->getSpanBytes());
        $this->assertEquals(1, $metrics->getMessages());
        $this->assertEquals(1, $metrics->getMessagesDropped());
        $this->assertEquals(4, $metrics->getMessageBytes());
        $this->assertEquals(5, $metrics->getQueuedSpans());
        $this->assertEquals(6, $metrics->getQueuedBytes());
    }
}
