<?php

namespace Zipkin\Reporters;

use Exception;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Zipkin\Recording\Span;
use Zipkin\Reporter;

class HttpLogging implements Reporter
{
    const DEFAULT_OPTIONS = [
        'host' => 'http://localhost:9411',
        'endpoint' => '/api/v2/spans',
        'muteErrors' => false,
        'contextOptions' => []
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

    public function __construct(ClientInterface $client, LoggerInterface $logger, array $options = [])
    {
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
        try {
            $this->client->request('POST', $this->options['host'] . $this->options['endpoint'], [
                'body' => json_encode(array_map(function(Span $span) {
                    return $span->toArray();
                }, $spans)),
            ]);
        } catch (Exception $e) {
            $this->logger->error(sprintf('traces were lost: %s', $e->getMessage()));
        }
    }
}