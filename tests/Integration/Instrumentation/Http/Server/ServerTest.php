<?php

declare(strict_types=1);

namespace ZipkinTests\Integration\Instrumentation\Http\Server;

use function FastRoute\simpleDispatcher;
use Zipkin\TracingBuilder;
use Zipkin\SpanCustomizer;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Reporters\InMemory;
use Zipkin\Propagation\TraceContext;

use Zipkin\Instrumentation\Http\Server\ServerTracing;
use Zipkin\Instrumentation\Http\Server\Parser;
use Zipkin\Instrumentation\Http\Server\Middleware;
use Zipkin\Instrumentation\Http\Server\DefaultParser;
use RingCentral\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use PHPUnit\Framework\TestCase;
use Middlewares\Utils\Factory;
use Middlewares\Utils\Dispatcher;
use Middlewares;
use FastRoute\RouteCollector;

final class ServerTest extends TestCase
{
    private static function createTracing(Parser $parser): array
    {
        $reporter = new InMemory();
        $tracing =
            TracingBuilder::create()
            ->havingReporter($reporter)
            ->havingSampler(BinarySampler::createAsAlwaysSample())
            ->build();
        $tracer = $tracing->getTracer();

        return [
            new ServerTracing($tracing, $parser),
            static function () use ($tracer, $reporter): array {
                $tracer->flush();
                return $reporter->flush();
            }
        ];
    }

    public function testMiddleware()
    {
        $parser = new class() extends DefaultParser {
            public function request(ServerRequestInterface $request, TraceContext $context, SpanCustomizer $span): void
            {
                // This parser retrieves the user_id from the request and add
                // is a tag.
                $userId = $request->getAttribute('user_id');
                $span->tag('user_id', $userId);
                parent::request($request, $context, $span);
            }
        };

        list($serverTracing, $flusher) = self::createTracing($parser);

        $fastRouteDispatcher = simpleDispatcher(function (RouteCollector $r) {
            $r->addRoute('GET', '/users/{user_id}', function ($request) {
                return new Response(201);
            });
        });

        $request = Factory::createServerRequest('GET', '/users/abc123');

        $response = Dispatcher::run([
            new Middlewares\FastRoute($fastRouteDispatcher),
            new Middleware($serverTracing),
            new Middlewares\RequestHandler(),
        ], $request);

        $this->assertEquals(201, $response->getStatusCode());

        $spans = ($flusher)();

        $this->assertCount(1, $spans);

        $span = $spans[0]->toArray();

        $this->assertEquals('GET', $span['name']);
        $this->assertEquals([
            'http.method' => 'GET',
            'http.path' => '/users/abc123',
            'http.status_code' => '201',
            'user_id' => 'abc123',
        ], $span['tags']);
    }
}
