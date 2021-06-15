<?php

declare(strict_types=1);

namespace Zipkin\Reporters\Aggregation;

use Memcached;
use Exception;

class MemcachedClient
{
    const GET_EXTENDED = Memcached::GET_EXTENDED;

    public const DEFAULT_OPTIONS = [
        Memcached::OPT_COMPRESSION => true,
    ];

    /**c
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

    /**
     * @param string       $server
     * @param int          $port
     * @param bool         $enableCompression
     */
    public function __construct(
        string $server = '127.0.0.1',
        int $port = 11211,
        array $options = []
    ) {
        $this->server = $server;
        $this->port = $port;

        $this->client = new Memcached();
        $this->client->addServer($this->server, $this->port);

        $options = \array_merge(self::DEFAULT_OPTIONS, $options);
        foreach($options as $key => $value) {
            $this->client->setOption($key, $value);
        }
    }

    /**
     * Check connection
     *
     * @return bool
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
     * Set an item
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $expiration
     */
    public function set($key, $value, $expiration = 0): bool
    {
        return $this->client->set($key, $value, $expiration);
    }

    /**
     * Get item by key.
     *
     * @param string $key
     * @param mixed  $cacheCallback
     * @param int    $flags
     *
     * @return mixed
     */
    public function get($key, $cacheCallback = null, $flags = null)
    {
        return $this->client->get($key, $cacheCallback, $flags);
    }

    /**
     * Compare and swap an item.
     *
     * @param float  $casToken
     * @param string $key
     * @param mixed  $value
     * @param int    $expiration
     */
    public function cas($casToken, $key, $value, $expiration = 0): bool
    {
        return $this->client->cas($casToken, $key, $value, $expiration);
    }

    /**
     * Quit all connections.
     *
     * @return bool
     */
    public function quit(): bool
    {
        return $this->client->quit();
    }
}
