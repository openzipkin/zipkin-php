<?php

declare(strict_types=1);

namespace ZipkinTests\Unit\Instrumentation\Http\Client\Psr18;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Zipkin\TracingBuilder;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Reporters\InMemory;
use Zipkin\Instrumentation\Http\Client\Psr18\Client;
use Zipkin\Instrumentation\Http\Client\HttpClientTracing;
use Zipkin\Instrumentation\Http\Client\DefaultHttpClientParser;
use RuntimeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Client\ClientInterface;
use PHPUnit\Framework\TestCase;

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
            new HttpClientTracing($tracing, new DefaultHttpClientParser),
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

        $span = $spans[0];

        $this->assertEquals('GET', $span->getName());
        $this->assertEquals([
            'http.method' => 'GET',
            'http.path' => '/',
        ], $span->getTags());
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

        $span = $spans[0];

        $this->assertEquals('GET', $span->getName());
        $this->assertEquals('CLIENT', $span->getKind());
        $this->assertEquals([
            'http.method' => 'GET',
            'http.path' => '/',
            'http.status_code' => '404',
            'error' => '404',
        ], $span->getTags());
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

        $span = $spans[0];

        $this->assertEquals('GET', $span->getName());
        $this->assertEquals([
            'http.method' => 'GET',
            'http.path' => '/'
        ], $span->getTags());
        $this->assertEquals('transport error', $span->getError()->getMessage());
    }
}
