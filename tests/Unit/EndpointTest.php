<?php

namespace ZipkinTests\Unit;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Zipkin\Endpoint;

final class EndpointTest extends PHPUnit_Framework_TestCase
{
    public function testEndpointFailsDueToInvalidServiceName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service name must be a string, got int');
        Endpoint::create(1);
    }

    public function testEndpointFailsDueToInvalidIpv4()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid IPv4. Expected something in the range 0.0.0.0 and 255.255.255.255, got 256.168.33.11'
        );
        Endpoint::create('my_service', '256.168.33.11');
    }

    public function testEndpointFailsDueToInvalidIpv6()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid IPv6 1200::AB00:1234::2552:7777:1313');
        Endpoint::create('my_service', null, '1200::AB00:1234::2552:7777:1313');
    }

    public function testEndpointFailsDueToInvalidPort()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid port. Expected a number between 0 and 65535, got 65536');
        Endpoint::create('my_service', null, null, 65536);
    }

    public function testEndpointIsCreatedSuccessfully()
    {
        $endpoint = Endpoint::create(
            'my_service',
            '192.168.33.11',
            '1200:0000:AB00:1234:0000:2552:7777:1313',
            1234
        );

        $this->assertEquals('my_service', $endpoint->getServiceName());
        $this->assertEquals('192.168.33.11', $endpoint->getIpv4());
        $this->assertEquals('1200:0000:AB00:1234:0000:2552:7777:1313', $endpoint->getIpv6());
        $this->assertEquals(1234, $endpoint->getPort());
    }

    public function testEndpointFromGlobalsIsCreatedSuccessfully()
    {
        $endpoint = Endpoint::createFromGlobals();
        $this->assertEquals(PHP_SAPI, $endpoint->getServiceName());
    }
}
