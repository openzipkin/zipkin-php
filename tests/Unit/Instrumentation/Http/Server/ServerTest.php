<?php

declare(strict_types=1);

namespace ZipkinTests\Unit\Instrumentation\Http\Server;

use Zipkin\TracingBuilder;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Reporters\InMemory;
use Zipkin\Instrumentation\Http\Server\ServerTracing;
use Zipkin\Instrumentation\Http\Server\Middleware;
use RingCentral\Psr7\Response as Psr7Response;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class ServerTest extends TestCase
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
            new ServerTracing($tracing),
            static function () use ($tracer, $reporter): array {
                $tracer->flush();
                return $reporter->flush();
            }
        ];
    }

    public function testMiddlewareHandlesRequestSuccessfully()
    {
        list($tracing, $flusher) = self::createTracing();
        $request = new ServerRequest('GET', 'http://mytest');

        $handler = new class() implements RequestHandlerInterface {
            private $lastRequest;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->lastRequest = $request;

                return new Psr7Response();
            }

            public function getLastRequest(): ?RequestInterface
            {
                return $this->lastRequest;
            }
        };

        $middleware = new Middleware($tracing);
        $middleware->process($request, $handler);

        $this->assertSame($request, $handler->getLastRequest());

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

    public function testMiddlewareParsesRequestSuccessfullyWithNon2xx()
    {
        list($tracing, $flusher) = self::createTracing();
        $request = new ServerRequest('GET', 'http://mytest');

        $handler = new class() implements RequestHandlerInterface {
            private $lastRequest;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->lastRequest = $request;

                return new Psr7Response(404);
            }

            public function getLastRequest(): ?RequestInterface
            {
                return $this->lastRequest;
            }
        };

        $middleware = new Middleware($tracing);
        $middleware->process($request, $handler);

        $this->assertSame($request, $handler->getLastRequest());

        $spans = ($flusher)();

        $this->assertCount(1, $spans);

        $span = $spans[0]->toArray();

        $this->assertEquals('GET', $span['name']);
        $this->assertEquals([
            'http.method' => 'GET',
            'http.path' => '/',
            'http.status_code' => '404',
            'error' => '404'
        ], $span['tags']);
    }
}
