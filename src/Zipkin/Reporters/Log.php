<?php

declare(strict_types=1);

namespace Zipkin\Reporters;

use Zipkin\Reporter;
use Zipkin\Recording\Span;
use Psr\Log\LoggerInterface;

final class Log implements Reporter
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Span[] $spans
     */
    public function report(array $spans): void
    {
        foreach ($spans as $span) {
            $this->logger->info($span->__toString());
        }
    }
}
