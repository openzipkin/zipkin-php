<?php

declare(strict_types=1);

namespace Zipkin\Reporters;

use Zipkin\Reporters\SpanSerializer;
use Zipkin\Reporters\JsonV2Serializer;
use Zipkin\Reporters\Http\CurlFactory;
use Zipkin\Reporters\Http\ClientFactory;
use Zipkin\Reporter;
use Zipkin\Recording\ReadbackSpan;
use RuntimeException;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

final class Bulk implements Reporter
{
    public const DEFAULT_OPTIONS = [
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
     * that will flood the logs on failure.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SpanSerializer
     */
    private $serializer;

    /**
     * @param array $options the options for HTTP call:
     *
     * <code>
     * $options = [
     *   'endpoint_url' => 'http://myzipkin:9411/api/v2/spans', // the reporting url for zipkin server
     *   'headers'      => ['X-API-Key' => 'abc123'] // the additional headers to be included in the request
     *   'timeout'      => 10, // the timeout for the request in seconds
     * ];
     * </code>
     *
     * @param ClientFactory $requesterFactory the factory for the client
     * that will do the HTTP call
     * @param LoggerInterface $logger the logger for output
     * @param SpanSerializer $serializer
     */
    public function __construct(
        array $options = [],
        ClientFactory $requesterFactory = null,
        LoggerInterface $logger = null,
        SpanSerializer $serializer = null
    ) {
        $this->options = \array_merge(self::DEFAULT_OPTIONS, $options);
        $this->clientFactory = $requesterFactory ?? CurlFactory::create();
        $this->logger = $logger ?? new NullLogger();
        $this->serializer = $serializer ?? new JsonV2Serializer();
    }

    /**
     * @param ReadbackSpan[] $spans
     * @return void
     */
    public function report(array $spans): void
    {
        if (\count($spans) === 0) {
            return;
        }

        $payload = $this->serializer->serialize($spans);

        if ($payload === false) {
            $this->logger->error(
                \sprintf('failed to encode spans with code %d', \json_last_error())
            );
            return;
        }

        $client = $this->clientFactory->build($this->options);

        try {
            $client($payload);
        } catch (RuntimeException $e) {
            $this->logger->error(\sprintf('failed to report spans: %s', $e->getMessage()));
        }
    }

    private function isDue(): bool
    {
        //
    }

    private function store(array $spans): bool
    {
        //
    }
}
