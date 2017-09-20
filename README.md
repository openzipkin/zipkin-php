# Zipkin instrumentation for PHP

[![Build Status](https://travis-ci.org/jcchavezs/zipkin-php.svg?branch=master)](https://travis-ci.org/jcchavezs/zipkin-php)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/packagist/l/jcchavezs/zipkin.svg)](https://github.com/jcchavezs/zipkin/blob/master/LICENSE)


This is a PHP library for OpenZipkin.

## Getting started

The recommended way to install Zipkin PHP is through [Composer](https://getcomposer.org/)

```bash
composer require jcchavezs/zipkin
```

## Example usage

```php
use Zipkin\Endpoint;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Timestamp;
use Zipkin\TracingBuilder;

$endpoint = Endpoint::createFromGlobals();
$reporter = new Zipkin\Reporters\NoopLogging();
$sampler = BinarySampler::createAsAlwaysSample();
$tracing = TracingBuilder::create()
    ->havingLocalEndpoint($endpoint)
    ->havingSampler($sampler)
    ->havingReporter($reporter)
    ->build();

$tracer = $tracing->getTracer();

$defaultSamplingFlags = DefaultSamplingFlags::createAsSampled();
$span = $tracer->newTrace($defaultSamplingFlags);
$span->setName('my_span_name');
$span->annotate('test_annotation', Timestamp\now());

$childSpan = $tracer->newChild($span->getContext());
$childSpan->start();
$childSpan->setName('my_child_span');
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
