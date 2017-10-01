<?php

namespace Zipkin;

use InvalidArgumentException;

class Endpoint
{
    const DEFAULT_SERVICE_NAME = 'unknown';

    /**
     * Service name in lowercase, such as "memcache" or "zipkin-web"
     * Conventionally, when the service name isn't known, service_name = "unknown".
     *
     * @var string
     */
    private $serviceName;

    /**
     * @var string host address packed into 4 bytes.
     */
    private $ipv4;

    /**
     * @var string
     */
    private $ipv6;

    /**
     * @var int
     */
    private $port;

    private function __construct($serviceName, $ipv4, $ipv6 = null, $port = null)
    {
        $this->serviceName = $serviceName;
        $this->ipv4 = $ipv4;
        $this->ipv6 = $ipv6;
        $this->port = $port;
    }

    /**
     * @param string $serviceName
     * @param int $ipv4
     * @param string $ipv6
     * @param int $port
     * @return Endpoint
     * @throws \InvalidArgumentException
     */
    public static function create($serviceName, $ipv4, $ipv6 = null, $port = null)
    {
        if ($serviceName !== (string) $serviceName) {
            throw new InvalidArgumentException(
                sprintf('service name must be a string, got %s', gettype($serviceName))
            );
        }

        if ($port !== null && (int) $port > 65535) {
            throw new InvalidArgumentException(
                sprintf('Invalid port. Expected a number between 0 and 65535, got %s', (string) $port)
            );
        }

        if (filter_var($ipv4, FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException(
                sprintf('Invalid IP. Expected something in the range 0.0.0.0 and 255.255.255.255, got %s', $ipv4)
            );
        }

        return new self($serviceName, $ipv4, $ipv6, (int) $port);
    }

    public static function createFromGlobals()
    {
        if (empty($_SERVER)) {
            throw new \RuntimeException('Could not fetch server information from CLI.');
        }

        return new self(
            $_SERVER['SERVER_SOFTWARE'] ?: self::DEFAULT_SERVICE_NAME,
            $_SERVER['REMOTE_ADDR'],
            null,
            $_SERVER['REMOTE_PORT']
        );
    }

    /**
     * @return Endpoint
     */
    public static function createAsEmpty()
    {
        return new self('', 0);
    }

    /**
     * @return string
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * @return string
     */
    public function getIpv4()
    {
        return $this->ipv4;
    }

    /**
     * @return string
     */
    public function getIpv6()
    {
        return $this->ipv6;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param string $serviceName
     * @return Endpoint
     */
    public function withServiceName($serviceName)
    {
        return new self($serviceName, $this->ipv4, $this->ipv6, $this->port);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $endpoint = [
            'serviceName' => $this->serviceName,
        ];

        if ($this->ipv4) {
            $endpoint['ipv4'] = $this->ipv4;
        }

        if ($this->port) {
            $endpoint['port'] = $this->port;
        }

        if ($this->ipv6) {
            $endpoint['ipv6'] = $this->ipv6;
        }

        return $endpoint;
    }
}
