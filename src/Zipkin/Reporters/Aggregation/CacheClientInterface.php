<?php

declare(strict_types=1);

namespace Zipkin\Reporters\Aggregation;

interface CacheClientInterface
{
    /**
     * Check Connection.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function ping(): bool;

    /**
     * Set a value.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $expiration
     *
     * @return bool
     */
    public function set($key, $value, $expiration): bool;

    /**
     * Get a value by key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key): ?string;

    /**
     * Delete a value by key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete($key): bool;

    /**
     * Quit all connections.
     *
     * @return bool
     */
    public function quit(): bool;
}
