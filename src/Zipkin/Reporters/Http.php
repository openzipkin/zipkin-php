<?php

declare(strict_types=1);

namespace Zipkin\Reporters;

use Zipkin\Reporters\SpanSerializer;
use Zipkin\Reporters\JsonV2Serializer;
use Zipkin\Reporters\Http\CurlFactory;
use Zipkin\Reporters\Http\ClientFactory;
use Zipkin\Reporter;
use Zipkin\Recording\Span;
use Zipkin\Recording\ReadbackSpan;
use TypeError;
use RuntimeException;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;

final class Http implements Reporter
{
    private const EMPTY_ARG = 'empty_arg';

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
        $options = self::EMPTY_ARG,
        $requesterFactory = null,
        LoggerInterface $logger = null,
        SpanSerializer $serializer = null
    ) {
        // V1 signature did not make much sense as in 99% of the cases, you don't
        // want to pass a ClientFactory to it but $options instead. There was a plan
        // to change that in v2 but it would be a breaking change, hence we added this
        // logic to accept new signature but keep compabitility with the old one.

        if ($options === self::EMPTY_ARG) {
            // means no arguments because first argument wasn't nullable in v1
            $parsedOptions = [];
        } elseif (\is_array($options) && (($requesterFactory instanceof ClientFactory) || $requesterFactory == null)) {
            // means the intention of the first argument is the `options`
            $parsedOptions = $options;
        } elseif ($options instanceof ClientFactory && (\is_array($requesterFactory) || $requesterFactory === null)) {
            // means the intention of the first argument is the `ClientFactory`
            $parsedOptions = $requesterFactory ?? [];
            $requesterFactory = $options;
        } elseif ($options === null) {
            $parsedOptions = $requesterFactory ?? [];
            $requesterFactory = null;
        } else {
            throw new TypeError(
                \sprintf(
                    'Argument 1 passed to %s::__construct must be of type array, %s given',
                    self::class,
                    \gettype($options)
                )
            );
        }

        $this->options = \array_merge(self::DEFAULT_OPTIONS, $parsedOptions);
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
}
