<?php

declare(strict_types=1);

namespace Zipkin\Reporters\Aggregation;

use Memcached;
use Exception;

class MemcachedClient implements CacheClientInterface
{
    /**
     * @var Memcached
     */
    private $client;

    /**
     * @var string
     */
    private $server;

    /**
     * @var int
     */
    private $port;

    public function __construct(
        string $server = '127.0.0.1',
        int $port = 11211,
        bool $enableCompression = false
    ) {
        $this->server = $server;
        $this->port = $port;

        $this->client = new Memcached();
        $this->client->setOption(Memcached::OPT_COMPRESSION, $enableCompression);
        $this->client->addServer($this->server, $this->port);
    }

    /**
     * {@inheritdoc}
     */
    public function ping(): bool
    {
        if (false === @fsockopen($this->server, $this->port)) {
            throw new Exception(
                "Unable to connect to memcached server {$this->server}:{$this->port}"
            );
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $expiration = 0): bool
    {
        return $this->client->set($key, $value, $expiration);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key): ?string
    {
        return $this->client->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key): bool
    {
        return $this->client->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function quit(): bool
    {
        return $this->client->quit();
    }
}
