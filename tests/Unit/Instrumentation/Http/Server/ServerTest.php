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

    private static function createRequestHandler($response = null): RequestHandlerInterface
    {
        return new class($response) implements RequestHandlerInterface {
            private $response;
            private $lastRequest;

            public function __construct(?ResponseInterface $response)
            {
                $this->response = $response ?? new Psr7Response();
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->lastRequest = $request;

                return $this->response;
            }

            public function getLastRequest(): ?RequestInterface
            {
                return $this->lastRequest;
            }
        };
    }

    public function testMiddlewareHandlesRequestSuccessfully()
    {
        list($tracing, $flusher) = self::createTracing();
        $request = new ServerRequest('GET', 'http://mytest');

        $handler = self::createRequestHandler();

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

        $handler = self::createRequestHandler(new Psr7Response(404));

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
