<?php

declare(strict_types=1);

namespace ZipkinTests\Instrumentation\Http\Client;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zipkin\Instrumentation\Http\Client\Client;
use Zipkin\TracingBuilder;
use ZipkinTests\Instrumentation\Http\Client\ClientTest;

final class ClientTest extends TestCase
{
    public function testClientSendRequestSuccess()
    {
        $client = new class() implements ClientInterface {
            private $lastRequest;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->lastRequest = $request;
                return new Response(200);
            }

            public function getLastRequest(): ?RequestInterface
            {
                return $this->lastRequest;
            }
        };

        $tracing = TracingBuilder::create()->build();
        $request = new Request('GET', 'http://mytest');
        $tracedClient = new Client($client, $tracing);
        $response = $tracedClient->sendRequest($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($request->getMethod(), $client->getLastRequest()->getMethod());
        $this->assertEquals($request->getUri(), $client->getLastRequest()->getUri());
    }
}
