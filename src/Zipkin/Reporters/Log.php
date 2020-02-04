<?php

declare(strict_types=1);

namespace Zipkin\Reporters;

use Psr\Log\LoggerInterface;
use Zipkin\Recording\Span;
use Zipkin\Reporter;

final class Log implements Reporter
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
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
