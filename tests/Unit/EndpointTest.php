<?php

namespace ZipkinTests\Unit;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Zipkin\Endpoint;

/**
 * @covers Endpoint
 */
final class EndpointTest extends PHPUnit_Framework_TestCase
{
    public function testEndpointFailsDueToInvalidServiceName()
    {
        $this->expectException(InvalidArgumentException::class);
        Endpoint::create(1, '192.168.33.11');
    }

    public function testEndpointFailsDueToInvalidIp4()
    {
        $this->expectException(InvalidArgumentException::class);
        Endpoint::create('my_service', '256.168.33.11');
    }

    public function testEndpointIsCreatedSuccessfully()
    {
        $endpoint = Endpoint::create('my_service', '192.168.33.11');

        $this->assertEquals('my_service', $endpoint->getServiceName());
        $this->assertEquals('192.168.33.11', $endpoint->getIpv4());
    }
}
