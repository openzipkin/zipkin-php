<?php

declare(strict_types=1);

namespace Zipkin;

use InvalidArgumentException;

final class Endpoint
{
    public const DEFAULT_SERVICE_NAME = 'unknown';

    /**
     * Service name in lowercase, such as "memcache" or "zipkin-web"
     * Conventionally, when the service name isn't known, service_name = "unknown".
     */
    private string $serviceName = self::DEFAULT_SERVICE_NAME;

    /**
     * Host address packed into 4 bytes.
     */
    private ?string $ipv4;

    private ?string $ipv6;

    private ?int $port;

    private function __construct(string $serviceName, ?string $ipv4, ?string $ipv6, ?int $port)
    {
        $this->serviceName = $serviceName;
        $this->ipv4 = $ipv4;
        $this->ipv6 = $ipv6;
        $this->port = $port;
    }

    public static function create(
        string $serviceName,
        ?string $ipv4 = null,
        ?string $ipv6 = null,
        ?int $port = null
    ): self {
        if ($ipv4 !== null && \filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            throw new InvalidArgumentException(
                \sprintf('Invalid IPv4. Expected something in the range 0.0.0.0 and 255.255.255.255, got %s', $ipv4)
            );
        }

        if ($ipv6 !== null && \filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            throw new InvalidArgumentException(
                \sprintf('Invalid IPv6 %s', $ipv6)
            );
        }

        if ($port !== null) {
            if ($port > 65535) {
                throw new InvalidArgumentException(
                    \sprintf('Invalid port. Expected a number between 0 and 65535, got %d', $port)
                );
            }
        }

        return new self($serviceName, $ipv4, $ipv6, $port);
    }

    public static function createFromGlobals(): self
    {
        return new self(
            PHP_SAPI,
            \array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] : null,
            null,
            \array_key_exists('REMOTE_PORT', $_SERVER) ? (int) $_SERVER['REMOTE_PORT'] : null
        );
    }

    public static function createAsEmpty(): self
    {
        return new self('unknown', null, null, null);
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getIpv4(): ?string
    {
        return $this->ipv4;
    }

    public function getIpv6(): ?string
    {
        return $this->ipv6;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function withServiceName(string $serviceName): Endpoint
    {
        return new self($serviceName, $this->ipv4, $this->ipv6, $this->port);
    }
}
