<?php

namespace ZipkinBundle;

use Zipkin\Instrumentation\Http\Server\Request as ServerRequest;
use Illuminate\Http\Request as IlluminateHttpRequest;

class Request extends ServerRequest
{
    /**
     * @var IlluminateHttpRequest
     */
    private $delegate;

    private $route;

    public function __construct(IlluminateHttpRequest $delegate)
    {
        $this->delegate = $delegate;
    }

    public function getMethod(): string
    {
        return $this->delegate->getMethod();
    }

    public function getPath(): ?string
    {
        return $this->delegate->getPathInfo() ?: '/';
    }

    public function getUrl(): string
    {
        return $this->delegate->getUri();
    }

    public function getHeader(string $name): string
    {
        return $this->delegate->headers->get($name);
    }

    /**
     * @return IlluminateHttpRequest
     */
    public function unwrap()
    {
        return $this->delegate;
    }

    public function getRoute(): ?string
    {
        
        return $this->delegate->route()->uri;
    }
}
