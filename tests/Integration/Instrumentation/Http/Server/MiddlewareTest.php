<?php

/**
 * Copyright 2020 OpenZipkin Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace ZipkinTests\Integration\Instrumentation\Http\Server;

use function FastRoute\simpleDispatcher;
use Zipkin\TracingBuilder;
use Zipkin\SpanCustomizer;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Reporters\InMemory;
use Zipkin\Propagation\TraceContext;

use Zipkin\Instrumentation\Http\Server\Request;
use Zipkin\Instrumentation\Http\Server\Psr15\Middleware;
use Zipkin\Instrumentation\Http\Server\HttpServerParser;
use Zipkin\Instrumentation\Http\Server\HttpServerTracing;
use Zipkin\Instrumentation\Http\Server\DefaultHttpServerParser;
use RingCentral\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Middlewares\Utils\Factory;
use Middlewares\Utils\Dispatcher;
use Middlewares;
use FastRoute\RouteCollector;

final class MiddlewareTest extends TestCase
{
    private static function createTracing(HttpServerParser $parser): array
    {
        $reporter = new InMemory();
        $tracing =
            TracingBuilder::create()
            ->havingReporter($reporter)
            ->havingSampler(BinarySampler::createAsAlwaysSample())
            ->build();
        $tracer = $tracing->getTracer();

        return [
            new HttpServerTracing($tracing, $parser),
            static function () use ($tracer, $reporter): array {
                $tracer->flush();
                return $reporter->flush();
            }
        ];
    }

    public function testMiddlewareRecordsRequestSuccessfully()
    {
        $parser = new class () extends DefaultHttpServerParser
        {
            public function request(Request $request, TraceContext $context, SpanCustomizer $span): void
            {
                // This parser retrieves the user_id from the request and add
                // is a tag.
                $userId = $request->unwrap()->getAttribute('user_id');
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
            'user_id' => 'abc123',
        ], $span['tags']);
    }
}
