# Zipkin instrumentation for PSR15 HTTP Server

This component contains the instrumentation for the standard [PSR15 HTTP servers](https://www.php-fig.org/psr/psr-15/).

## Getting started

Before using this library, make sure the interfaces for PSR15 HTTP server are installed:

```bash
composer require psr/http-server-middleware
```

## Usage

In this example we use [fast-route](https://github.com/middlewares/fast-route) and [request-handler](https://github.com/middlewares/request-handler) middlewares but any HTTP server middleware supporting PSR15 middlewares will work.

```php
use Zipkin\Instrumentation\Http\Server\Psr15\Middleware as ZipkinMiddleware;
use Zipkin\Instrumentation\Http\Server\HttpServerTracing;

// Create the routing dispatcher
$fastRouteDispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $r->get('/hello/{name}', HelloWorldController::class);
});

// Creates tracing component
$tracing = TracingBuilder::create()
            ->havingLocalServiceName('my_service')
            ->build();

$httpClientTracing = new HttpServerTracing($tracing);

$dispatcher = new Dispatcher([
    new Middlewares\FastRoute($fastRouteDispatcher),
    // ...
    new ZipkinMiddleware($serverTracing),
    new Middlewares\RequestHandler(),
]);

$response = $dispatcher->dispatch(new ServerRequest('/hello/world'));
```
