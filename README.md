# Zipkin instrumentation for PHP

[![Build Status](https://travis-ci.org/jcchavezs/zipkin-php.svg?branch=master)](https://travis-ci.org/jcchavezs/zipkin-php)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)](https://php.net/)
[![Total Downloads](https://poser.pugx.org/jcchavezs/zipkin/downloads)](https://packagist.org/packages/jcchavezs/zipkin)
[![License](https://img.shields.io/packagist/l/jcchavezs/zipkin.svg)](https://github.com/jcchavezs/zipkin-php/blob/master/LICENSE)

This is a **production ready** PHP library for Zipkin.

This is a simple example of usage, for a more complete frontend/backend 
example, check [this repository](https://github.com/openzipkin/zipkin-php-example).

## Installation

```bash
composer require jcchavezs/zipkin
```

## Setup

```php
use GuzzleHttp\Client;
use Zipkin\Annotation;
use Zipkin\Endpoint;
use Zipkin\Samplers\BinarySampler;
use Zipkin\TracingBuilder;
use Zipkin\Reporters\HttpLogging;

$endpoint = Endpoint::createFromGlobals();
$client = new Client();

// Logger to stdout
$logger = new \Monolog\Logger('log');
$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());

$reporter = new HttpLogging($client, $logger);
$sampler = BinarySampler::createAsAlwaysSample();
$tracing = TracingBuilder::create()
    ->havingLocalEndpoint($endpoint)
    ->havingSampler($sampler)
    ->havingReporter($reporter)
    ->build();

$tracer = $tracing->getTracer();

...

$tracer->flush();
```

## Tracing

The tracer creates and joins spans that model the latency of potentially
distributed work. It can employ sampling to reduce overhead in process
or to reduce the amount of data sent to Zipkin.

Spans returned by a tracer report data to Zipkin when finished, or do
nothing if unsampled. After starting a span, you can annotate events of
interest or add tags containing details or lookup keys.

Spans have a context which includes trace identifiers that place it at
the correct spot in the tree representing the distributed operation.

### Local Tracing

When tracing local code, just run it inside a span

```php
$span = $tracer->newTrace()->setName('encode')->start();

try {
  doSomethingExpensive();
} finally {
  $span->finish();
}
```

In the above example, the span is the root of the trace. In many cases,
you will be a part of an existing trace. When this is the case, call
`newChild` instead of `newTrace`

```php
$span = $tracer->newChild($root->getContext())->setName('encode')->start();
try {
  doSomethingExpensive();
} finally {
  $span->finish();
}
```

### Customizing spans
Once you have a span, you can add tags to it, which can be used as lookup
keys or details. For example, you might add a tag with your runtime
version.

```php
$span->tag('http.status_code', '200');
```

### RPC tracing

RPC tracing is often done automatically by interceptors. Under the scenes,
they add tags and events that relate to their role in an RPC operation.

Here's an example of a client span:

```php
// before you send a request, add metadata that describes the operation
$span = $tracer->newTrace()->setName('get')->setKind(Kind\CLIENT);
$span->tag('clnt/finagle.version', '6.36.0');
$span->tag(Tags\HTTP_PATH, '/api');
$span->setRemoteEndpoint(Remote::create('backend', 127 << 24 | 1, null, 8080);

// when the request is scheduled, start the span
$span->start();

// if you have callbacks for when data is on the wire, note those events
$span->annotate(Annotation\WIRE_SEND);
$span->annotate(Annotation\WIRE_RECV);

// when the response is complete, finish the span
$span->finish();
```

#### One-Way tracing

Sometimes you need to model an asynchronous operation, where there is a
request, but no response. In normal RPC tracing, you use `$span->finish()`
which indicates the response was received. In one-way tracing, you use
`$span->flush()` instead, as you don't expect a response.

Here's how a client might model a one-way operation

```php
// start a new span representing a client request
$oneWaySend = $tracer->newChild($parent)->setKind(Kind\CLIENT);

// Add the trace context to the request, so it can be propagated in-band
$tracing->getPropagation()->getInjector(new RequestHeaders)
                     ->inject($oneWaySend->getContext(), $request);

// fire off the request asynchronously, totally dropping any response
$client->execute($request);

// start the client side and flush instead of finish
$oneWaySend->start()->flush();
```

And here's how a server might handle this...

```php
// pull the context out of the incoming request
$extractor = $tracing->getPropagation()->getExtractor(new RequestHeaders);

// convert that context to a span which you can name and add tags to
$oneWayReceive = $tracer->newChild($extractor($request))
    ->setName('process-request')
    ->setKind(Kind\SERVER)
    ... add tags etc.

// start the server side and flush instead of finish
$oneWayReceive->start()->flush();

// you should not modify this span anymore as it is complete. However,
// you can create children to represent follow-up work.
$next = $tracer->newChild($oneWayReceive->getContext())->setName('step2')->start();
```

## Sampling

Sampling may be employed to reduce the data collected and reported out
of process. When a span isn't sampled, it adds no overhead (noop).

Sampling is an up-front decision, meaning that the decision to report
data is made at the first operation in a trace, and that decision is
propagated downstream.

By default, there's a global sampler that applies a single rate to all
traced operations. `Sampler` is how you indicate this,
and it defaults to trace every request.

### Custom sampling

You may want to apply different policies depending on what the operation
is. For example, you might not want to trace requests to static resources
such as images, or you might want to trace all requests to a new api.

Most users will use a framework interceptor which automates this sort of
policy. Here's how they might work internally.

```php
private function newTrace(Request $request) {
  $flags = SamplingFlags::createAsEmpty();
  if (strpos($request->getUri(), '/experimental') === 0) {
    $flags = DefaultSamplingFlags::createAsSampled();
  } else if (strpos($request->getUri(), '/static') === 0) {
    $flags = DefaultSamplingFlags::createAsSampled();
  }
  return $tracer->newTrace($flags);
}
```

## Propagation
Propagation is needed to ensure activity originating from the same root
are collected together in the same trace. The most common propagation
approach is to copy a trace context from a client sending an RPC request
to a server receiving it.

For example, when an downstream Http call is made, its trace context is
sent along with it, encoded as request headers:

```
   Client Span                                                Server Span
┌──────────────────┐                                       ┌──────────────────┐
│                  │                                       │                  │
│   TraceContext   │           Http Request Headers        │   TraceContext   │
│ ┌──────────────┐ │          ┌───────────────────┐        │ ┌──────────────┐ │
│ │ TraceId      │ │          │ X─B3─TraceId      │        │ │ TraceId      │ │
│ │              │ │          │                   │        │ │              │ │
│ │ ParentSpanId │ │ Extract  │ X─B3─ParentSpanId │ Inject │ │ ParentSpanId │ │
│ │              ├─┼─────────>│                   ├────────┼>│              │ │
│ │ SpanId       │ │          │ X─B3─SpanId       │        │ │ SpanId       │ │
│ │              │ │          │                   │        │ │              │ │
│ │ Sampled      │ │          │ X─B3─Sampled      │        │ │ Sampled      │ │
│ └──────────────┘ │          └───────────────────┘        │ └──────────────┘ │
│                  │                                       │                  │
└──────────────────┘                                       └──────────────────┘
```

The names above are from [B3 Propagation](https://github.com/openzipkin/b3-propagation),
which is built-in to Brave and has implementations in many languages and
frameworks.

Most users will use a framework interceptor which automates propagation.
Here's how they might work internally.

Here's what client-side propagation might look like

```php
// configure a function that injects a trace context into a request
$injector = $tracing->getPropagation()->getInjector(new RequestHeaders);

// before a request is sent, add the current span's context to it
$injector->inject($span->getContext(), $request);
```

Here's what server-side propagation might look like

```php
// configure a function that extracts the trace context from a request
$extracted = $tracing->getPropagation()->extractor(new RequestHeaders);

$span = $tracer->newChild($extracted)->setKind(Kind\SERVER);
```

### Extracting a propagated context
The `Extractor` reads trace identifiers and sampling status
from an incoming request or message. The carrier is usually a request object
or headers.

`SamplingFlags|TraceContext` is usually only used with `$tracer->newChild(extracted)`, unless you are
sharing span IDs between a client and a server.

### Sharing span IDs between client and server

A normal instrumentation pattern is creating a span representing the server
side of an RPC. `Extractor::__invoke` might return a complete trace context when
applied to an incoming client request. `$tracer->joinSpan` attempts to continue
the this trace, using the same span ID if supported, or creating a child span
if not. When span ID is shared, data reported includes a flag saying so.

Here's an example of B3 propagation:

```
                              ┌───────────────────┐      ┌───────────────────┐
 Incoming Headers             │   TraceContext    │      │   TraceContext    │
┌───────────────────┐(extract)│ ┌───────────────┐ │(join)│ ┌───────────────┐ │
│ X─B3-TraceId      │─────────┼─┼> TraceId      │ │──────┼─┼> TraceId      │ │
│                   │         │ │               │ │      │ │               │ │
│ X─B3-ParentSpanId │─────────┼─┼> ParentSpanId │ │──────┼─┼> ParentSpanId │ │
│                   │         │ │               │ │      │ │               │ │
│ X─B3-SpanId       │─────────┼─┼> SpanId       │ │──────┼─┼> SpanId       │ │
└───────────────────┘         │ │               │ │      │ │               │ │
                              │ │               │ │      │ │  Shared: true │ │
                              │ └───────────────┘ │      │ └───────────────┘ │
                              └───────────────────┘      └───────────────────┘
```

Some propagation systems only forward the parent span ID. In this case, a new span ID is always provisioned and the incoming context determines the parent ID.

Here's an example of AWS propagation:

```
                              ┌───────────────────┐      ┌───────────────────┐
 x-amzn-trace-id              │   TraceContext    │      │   TraceContext    │
┌───────────────────┐(extract)│ ┌───────────────┐ │(join)│ ┌───────────────┐ │
│ Root              │─────────┼─┼> TraceId      │ │──────┼─┼> TraceId      │ │
│                   │         │ │               │ │      │ │               │ │
│ Parent            │─────────┼─┼> SpanId       │ │──────┼─┼> ParentSpanId │ │
└───────────────────┘         │ └───────────────┘ │      │ │               │ │
                              └───────────────────┘      │ │  SpanId: New  │ │
                                                         │ └───────────────┘ │
                                                         └───────────────────┘
```

### Implementing Propagation

`Extractor` will output a `SamplingFlags|TraceContext` with one of the following:

* `TraceContext` if trace and span IDs were present.
* `SamplingFlags` if no identifiers were present

## Tests

Tests can be run by

```bash
composer test
```

## Reference

* [Instrumenting a library](http://zipkin.io/pages/instrumenting.html)
* [openzipkin/zipkin-api](https://github.com/openzipkin/zipkin-api)
