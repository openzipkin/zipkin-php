<?php

declare(strict_types=1);

namespace Zipkin\Reporters;

use Zipkin\Reporter;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Zipkin\Reporters\SpanSerializer;
use Zipkin\Reporters\JsonV2Serializer;
use Zipkin\Reporters\Aggregation\MemcachedClient;
use Exception;

final class Memcached implements Reporter
{
    public const DEFAULT_OPTIONS = [
        'cache_key' => 'zipkin_traces',
    ];

    /**
     * @var array
     */
    private $options;

    /**
     * @var MemcachedClient
     */
    private $memcachedClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SpanSerializer
     */
    private $serializer;

    /**
     * @param array           $options
     * @param MemcachedClient $memcachedClient
     * @param LoggerInterface $logger
     * @param SpanSerializer  $serializer
     */
    public function __construct(
        array $options = [],
        MemcachedClient $memcachedClient = null,
        LoggerInterface $logger = null,
        SpanSerializer $serializer = null
    ) {
        $this->options = \array_merge(self::DEFAULT_OPTIONS, $options);
        $this->memcachedClient = $memcachedClient ?? new MemcachedClient();
        $this->logger = $logger ?? new NullLogger();
        $this->serializer = $serializer ?? new JsonV2Serializer();
    }

    /**
     * @param  array  $spans
     */
    public function report(array $spans): void
    {
        try {
            $this->memcachedClient->ping();

            // Fetch stored traces
            $result = $this->memcachedClient->get(
                $this->options['cache_key'],
                null,
                MemcachedClient::GET_EXTENDED
            );

            $payload = $this->serializer->serialize($spans);

            if ($payload === false) {
                $this->logger->error(
                    \sprintf('failed to encode spans with code %d', \json_last_error())
                );
            }

            // Store traces if there aren't any previous traces
            if (empty($result)) {
                $this->memcachedClient->set($this->options['cache_key'], $payload);
                $this->memcachedClient->quit();
                return;
            }

            $status = false;

            // Merge the new traces with the stored traces only if
            // the item not updated by a different concurrent proceess
            while (!$status) {
                $result['value'] = array_merge(
                    json_decode($result['value'], true),
                    json_decode($payload, true)
                );

                $status = $this->memcachedClient->cas(
                    $result['cas'],
                    $this->options['cache_key'],
                    json_encode($result['value'])
                );

                if (!$status) {
                    $result = $this->memcachedClient->get(
                        $this->options['cache_key'],
                        null,
                        MemcachedClient::GET_EXTENDED
                    );
                }
            }

            $this->memcachedClient->quit();

            return;
        } catch (Exception $e) {
            $this->logger->error(
                \sprintf('Error while calling memcached server: %s', $e->getMessage())
            );
        }
    }

    /**
     * @return array
     */
    public function flush(): array
    {
        try {
            $this->memcachedClient->ping();

            // Fetch stored traces
            $result = $this->memcachedClient->get(
                $this->options['cache_key'],
                null,
                MemcachedClient::GET_EXTENDED
            );

            if (empty($result)) {
                return [];
            }

            $status = false;

            // Return stored traces and set the key value as empty only if
            // the item not updated by a different concurrent proceess
            while (!$status) {
                $status = $this->memcachedClient->cas(
                    $result['cas'],
                    $this->options['cache_key'],
                    json_encode([])
                );

                if (!$status) {
                    $result = $this->memcachedClient->get(
                        $this->options['cache_key'],
                        null,
                        MemcachedClient::GET_EXTENDED
                    );
                }
            }

            $this->memcachedClient->quit();

            return json_decode($result['value'], true);
        } catch (Exception $e) {
            $this->logger->error(
                \sprintf('Error while calling memcached server: %s', $e->getMessage())
            );
        }
    }
}
