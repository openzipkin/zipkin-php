<?php

declare(strict_types=1);

namespace ZipkinTests\Unit\Instrumentation\Http\Server\Psr15;

use Zipkin\TracingBuilder;
use Zipkin\Span;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Reporters\InMemory;
use Zipkin\Propagation\TraceContext;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Instrumentation\Http\Server\Psr15\Middleware;
use Zipkin\Instrumentation\Http\Server\Psr15\DefaultParser;
use Zipkin\Instrumentation\Http\Server\HttpServerTracing;
use RingCentral\Psr7\Response as Psr7Response;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class ServerTest extends TestCase
{
    private static function createTracing(callable $requestSampler = null): array
    {
        $reporter = new InMemory();
        $tracing =
            TracingBuilder::create()
            ->havingReporter($reporter)
            ->havingSampler(BinarySampler::createAsAlwaysSample())
            ->build();
        $tracer = $tracing->getTracer();

        return [
            new HttpServerTracing($tracing, new DefaultParser, $requestSampler),
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

    public function testMiddlewareKeepsContextForJoinSpan()
    {
        $request = new ServerRequest('GET', 'http://mytest');

        $extractedContext = TraceContext::createAsRoot(DefaultSamplingFlags::createAsSampled());

        list($tracing) = self::createTracing();
        $middleware = new Middleware($tracing);

        /**
         * @var Span $span
         */
        $span = $this->invokePrivateMethod($middleware, 'nextSpan', [$extractedContext, $request]);
        $this->assertSame($extractedContext->getTraceId(), $span->getContext()->getTraceId());
    }

    /**
     * @dataProvider middlewareNextSpanProvider
     */
    public function testMiddlewareNextSpanResolvesSampling(
        $extractedContext,
        callable $requestSampler,
        ?bool $expectedSampling
    ) {
        $request = new ServerRequest('GET', 'http://mytest');

        list($tracing) = self::createTracing($requestSampler);
        $middleware = new Middleware($tracing);

        $span = $this->invokePrivateMethod($middleware, 'nextSpan', [$extractedContext, $request]);
        $this->assertEquals($expectedSampling, $span->getContext()->isSampled());
    }

    public function middlewareNextSpanProvider(): array
    {
        return [
            //[$extractedContext, $requestSampler, $expectedSampling]
            'no context becomes sampled' => [null, function () {
                return true;
            }, true],
            'not sampled becomes sampled' => [DefaultSamplingFlags::createAsNotSampled(), function () {
                return true;
            }, true],
            'sampled remains the same' => [DefaultSamplingFlags::createAsSampled(), function () {
                return false;
            }, true],
        ];
    }

    private function invokePrivateMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
