<?php

declare(strict_types=1);

namespace ZipkinTests\Instrumentation\Http\Client;

use Zipkin\TracingBuilder;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Zipkin\Reporters\InMemory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Zipkin\Instrumentation\Http\Client\Client;
use Zipkin\Instrumentation\Http\Client\ClientTracing;
use Zipkin\Samplers\BinarySampler;

final class ClientTest extends TestCase
{
    private static function createTracing(): array
    {
        $reporter = new InMemory();
        $tracing =
            TracingBuilder::create()
            ->havingReporter($reporter)
            ->havingSampler(BinarySampler::createAsAlwaysSample())
            ->build();
        $tracer = $tracing->getTracer();

        return [
            new ClientTracing($tracing),
            static function () use ($tracer, $reporter): array {
                $tracer->flush();
                return $reporter->flush();
            }
        ];
    }

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

        list($tracing, $flusher) = self::createTracing();
        $tracedClient = new Client($client, $tracing);
        $request = new Request('GET', 'http://mytest');
        $response = $tracedClient->sendRequest($request);

        $this->assertTrue($client->getLastRequest()->hasHeader('X-B3-TraceId'));
        $this->assertTrue($client->getLastRequest()->hasHeader('X-B3-SpanId'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($request->getMethod(), $client->getLastRequest()->getMethod());
        $this->assertEquals($request->getUri(), $client->getLastRequest()->getUri());

        $spans = ($flusher)();

        $this->assertCount(1, $spans);

        $span = $spans[0]->toArray();

        $this->assertEquals('GET', $span['name']);
        $this->assertEquals([
            'http.method' => 'GET',
            'http.path' => '/',
            'http.status_code' => '200',
        ], $span['tags']);
    }

    public function testClientSendRequestSuccessWithNon2xx()
    {
        $client = new class() implements ClientInterface {
            private $lastRequest;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->lastRequest = $request;
                return new Response(404);
            }

            public function getLastRequest(): ?RequestInterface
            {
                return $this->lastRequest;
            }
        };

        list($tracing, $flusher) = self::createTracing();
        $tracedClient = new Client($client, $tracing);
        $request = new Request('GET', 'http://mytest');
        $response = $tracedClient->sendRequest($request);

        $this->assertTrue($client->getLastRequest()->hasHeader('X-B3-TraceId'));
        $this->assertTrue($client->getLastRequest()->hasHeader('X-B3-SpanId'));

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals($request->getMethod(), $client->getLastRequest()->getMethod());
        $this->assertEquals($request->getUri(), $client->getLastRequest()->getUri());

        $spans = ($flusher)();

        $this->assertCount(1, $spans);

        $span = $spans[0]->toArray();

        $this->assertEquals('GET', $span['name']);
        $this->assertEquals([
            'http.method' => 'GET',
            'http.path' => '/',
            'http.status_code' => '404',
            'error' => '404',
        ], $span['tags']);
    }

    public function testClientSendRequestFails()
    {
        $client = new class() implements ClientInterface {
            private $lastRequest;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->lastRequest = $request;
                throw new RuntimeException('transport error');
            }

            public function getLastRequest(): ?RequestInterface
            {
                return $this->lastRequest;
            }
        };

        list($tracing, $flusher) = self::createTracing();
        $tracedClient = new Client($client, $tracing);
        $request = new Request('GET', 'http://mytest');
        try {
            $tracedClient->sendRequest($request);
            $this->fail('should not reach this');
        } catch (\Throwable $e) {
        }

        $this->assertTrue($client->getLastRequest()->hasHeader('X-B3-TraceId'));
        $this->assertTrue($client->getLastRequest()->hasHeader('X-B3-SpanId'));

        $spans = ($flusher)();

        $this->assertCount(1, $spans);

        $span = $spans[0]->toArray();

        $this->assertEquals('GET', $span['name']);
        $this->assertEquals([
            'http.method' => 'GET',
            'http.path' => '/',
            'error' => 'transport error',
        ], $span['tags']);
    }
}
