<?php

declare(strict_types=1);

namespace Zipkin\Reporters;

use RuntimeException;
use Zipkin\Recording\Span;
use Zipkin\Reporter;
use Zipkin\Reporters\Http\ClientFactory;
use Zipkin\Reporters\Http\CurlFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Http implements Reporter
{
    const DEFAULT_OPTIONS = [
        'endpoint_url' => 'http://localhost:9411/api/v2/spans',
    ];

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var array
     */
    private $options;

    /**
     * logger is only meant to be used for development purposes. Enabling
     * an actual logger in production could cause a massive amount of data
     * that will flood the logs.
     *
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ?ClientFactory $requesterFactory = null,
        array $options = [],
        ?LoggerInterface $logger = null
    ) {
        $this->clientFactory = $requesterFactory ?: CurlFactory::create();
        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param Span[] $spans
     * @return void
     */
    public function report(array $spans): void
    {
        if (count($spans) === 0) {
            return;
        }

        $payload = json_encode(array_map(function (Span $span) {
            return $span->toArray();
        }, $spans));
        if ($payload === false) {
            $this->logger->error(
                sprintf('failed to encode spans with code %d', \json_last_error())
            );
            return;
        }

        $client = $this->clientFactory->build($this->options);
        try {
            $client($payload);
        } catch (RuntimeException $e) {
            $this->logger->error(sprintf('failed to report spans: %s', $e->getMessage()));
        }
    }
}
