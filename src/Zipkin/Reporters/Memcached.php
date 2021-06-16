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
        'cache_key_prefix' => 'zipkin_traces',
        'batch_interval' => 60, // In seconds
        'batch_size' => 100
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
     * @var Http
     */
    private $httpClient;

    /**
     * @param array           $options
     * @param Http $httpClient
     * @param MemcachedClient $memcachedClient
     * @param LoggerInterface $logger
     * @param SpanSerializer  $serializer
     */
    public function __construct(
        array $options = [],
        Http $httpClient,
        MemcachedClient $memcachedClient = null,
        LoggerInterface $logger = null,
        SpanSerializer $serializer = null
    ) {
        $this->options = \array_merge(self::DEFAULT_OPTIONS, $options);
        $this->httpClient = $httpClient;
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
            $status = false;

            $this->memcachedClient->ping();

            while (!$status) {
                $result = $this->memcachedClient->get(
                    sprintf("%s_spans", $this->options['cache_key_prefix']),
                    null,
                    MemcachedClient::GET_EXTENDED
                );

                $payload = serialize($spans);

                if (empty($result)) {
                    $this->memcachedClient->set(
                        sprintf("%s_spans", $this->options['cache_key_prefix']),
                        serialize($spans)
                    );
                    $this->memcachedClient->quit();
                    return;
                }

                $result['value'] = array_merge(
                    unserialize($result['value']),
                    $spans
                );

                // If batch reporting interval passed and enabled, send spans to zipkin server and reset the value
                if ($this->isBatchIntervalPassed()) {
                    $this->httpClient->report($result['value']);
                    $result['value'] = [];
                    $this->resetBatchInterval();
                }

                // If batch reporting size reached and enabled, send spans to zipkin server and reset the value
                if (($this->options['batch_size'] > 0) && (count($result['value']) >= $this->options['batch_size'])) {
                    $this->httpClient->report($result['value']);
                    $result['value'] = [];
                }

                $status = $this->memcachedClient->compareAndSwap(
                    $result['cas'],
                    sprintf("%s_spans", $this->options['cache_key_prefix']),
                    serialize($result['value'])
                );
            }
        } catch (Exception $e) {
            $this->logger->error(
                \sprintf('Error while calling memcached server: %s', $e->getMessage())
            );
            // If memcache is down or there was any failure happened, send spans directly to zipkin
            $this->httpClient->report($spans);
        } finally {
            $this->memcachedClient->quit();
        }

        return;
    }

    /**
     * @return array
     */
    public function flush(): array
    {
        try {
            $status = false;

            $this->memcachedClient->ping();

            while (!$status) {
                $result = $this->memcachedClient->get(
                    sprintf("%s_spans", $this->options['cache_key_prefix']),
                    null,
                    MemcachedClient::GET_EXTENDED
                );

                if (empty($result)) {
                    $this->memcachedClient->quit();
                    return [];
                }

                $status = $this->memcachedClient->compareAndSwap(
                    $result['cas'],
                    sprintf("%s_spans", $this->options['cache_key_prefix']),
                    serialize([])
                );
            }

            $this->memcachedClient->quit();

            return unserialize($result['value']);
        } catch (Exception $e) {
            $this->logger->error(
                \sprintf('Error while calling memcached server: %s', $e->getMessage())
            );
        }

        return [];
    }

    /**
     * @return boolean
     */
    private function isBatchIntervalPassed(): bool
    {
        if ($this->options['batch_interval'] <= 0) {
            return false;
        }

        $result = $this->memcachedClient->get(
            sprintf("%s_batch_ts", $this->options['cache_key_prefix']),
            null,
            MemcachedClient::GET_EXTENDED
        );

        if (empty($result)) {
            return true;
        }

        return ($result['value'] + $this->options['batch_interval']) <= time());
    }

    /**
     * Reset Batch Interval
     *
     * @return bool
     */
    private function resetBatchInterval(): bool
    {
        if ($this->options['batch_interval'] <= 0) {
            return false;
        }

        try {
            $status = false;

            $this->memcachedClient->ping();

            while (!$status) {
                $result = $this->memcachedClient->get(
                    sprintf("%s_batch_ts", $this->options['cache_key_prefix']),
                    null,
                    MemcachedClient::GET_EXTENDED
                );

                if (empty($result)) {
                    $this->memcachedClient->set(
                        sprintf("%s_batch_ts", $this->options['cache_key_prefix']),
                        time()
                    );

                    $this->memcachedClient->quit();

                    return true;
                }

                $status = $this->memcachedClient->compareAndSwap(
                    $result['cas'],
                    sprintf("%s_batch_ts", $this->options['cache_key_prefix']),
                    time()
                );
            }

            $this->memcachedClient->quit();
        } catch (Exception $e) {
            $this->logger->error(
                \sprintf('Error while calling memcached server: %s', $e->getMessage())
            );
        }

        return true;
    }
}
