# Zipkin instrumentation for PHP

[![Build Status](https://travis-ci.org/jcchavezs/zipkin-php.svg?branch=master)](https://travis-ci.org/jcchavezs/zipkin-php)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/packagist/l/jcchavezs/zipkin.svg)](https://github.com/jcchavezs/zipkin-php/blob/master/LICENSE)

This is a PHP library for OpenZipkin.

## Installation

```bash
composer require jcchavezs/zipkin
```

## Example usage

```php
use GuzzleHttp\Client;
use Zipkin\Annotation;
use Zipkin\Endpoint;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Timestamp;
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

$defaultSamplingFlags = DefaultSamplingFlags::createAsSampled();
$span = $tracer->newTrace($defaultSamplingFlags);
$span->start(Timestamp\now());
$span->setName('my_span_name');
$span->annotate(Annotation::SERVER_RECEIVE, Timestamp\now());

...

$childSpan = $tracer->newChild($span->getContext());
$childSpan->start();
$childSpan->setName('my_child_span');

...

$childSpan->finish(Timestamp\now());

$span->finish(Timestamp\now());

$tracer->flush();
```

## Tests

Tests can be run by
```bash
composer test
```

## Reference

* [Instrumenting a library](http://zipkin.io/pages/instrumenting.html)
* [openzipkin/zipkin-api](https://github.com/openzipkin/zipkin-api)
