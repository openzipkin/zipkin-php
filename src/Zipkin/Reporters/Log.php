<?php

declare(strict_types=1);

namespace Zipkin\Reporters;

use Zipkin\Reporter;
use Zipkin\Recording\Span;
use Psr\Log\LoggerInterface;

final class Log implements Reporter
{
    private LoggerInterface $logger;

    private SpanSerializer $serializer;

    public function __construct(
        LoggerInterface $logger,
        SpanSerializer $serializer = null
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer ?? new JsonV2Serializer();
    }

    /**
     * @param Span[] $spans
     */
    public function report(array $spans): void
    {
        $this->logger->info($this->serializer->serialize($spans));
    }
}
