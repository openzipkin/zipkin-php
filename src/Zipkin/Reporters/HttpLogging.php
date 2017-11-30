<?php

namespace Zipkin\Reporters;

use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Zipkin\Recording\Span;
use Zipkin\Reporter;

final class HttpLogging implements Reporter
{
    const DEFAULT_OPTIONS = [
        'baseUrl' => 'http://localhost:9411',
        'endpoint' => '/api/v2/spans',
    ];

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $options;

    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger,
        array $options = []
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);
    }

    /**
     * @param Span[] $spans
     * @return void
     */
    public function report(array $spans)
    {
        $body = json_encode(array_map(function (Span $span) {
            return $span->toArray();
        }, $spans));

        try {
            $this->client->request(
                'POST',
                $this->options['baseUrl'] . $this->options['endpoint'],
                ['body' => $body]
            );
        } catch (GuzzleException $e) {
            $this->logger->error(sprintf('traces were lost: %s', $e->getMessage()));
        }
    }
}
