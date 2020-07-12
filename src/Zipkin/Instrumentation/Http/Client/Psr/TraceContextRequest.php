<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client\Psr;

use Zipkin\Propagation\TraceContext;
use Zipkin\Propagation\SamplingFlags;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\RequestInterface;

final class TraceContextRequest implements RequestInterface
{
    /**
     * @var RequestInterface
     */
    private $delegate;

    /**
     * @var TraceContext
     */
    private $context;

    private function __construct(RequestInterface $request, TraceContext $context)
    {
        $this->delegate = $request;
        $this->context = $context;
    }

    public static function wrap(RequestInterface $request, TraceContext $context): self
    {
        return new self($request, $context);
    }

    public static function obtainContext(RequestInterface $request): ?SamplingFlags
    {
        if ($request instanceof self) {
            return $request->context;
        }

        return null;
    }

    public function getTraceContext(): TraceContext
    {
        return $this->context;
    }

    public function getRequestTarget()
    {
        return $this->delegate->getRequestTarget();
    }

    public function withRequestTarget($requestTarget)
    {
        return new self($this->delegate->withRequestTarget($requestTarget), $this->context);
    }

    public function getMethod()
    {
        return $this->delegate->getMethod();
    }

    public function withMethod($method)
    {
        return new self($this->delegate->withMethod($method), $this->context);
    }

    public function getUri()
    {
        return $this->delegate->getUri();
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        return new self($this->delegate->withUri($uri, $preserveHost), $this->context);
    }

    public function getProtocolVersion()
    {
        return $this->delegate->getProtocolVersion();
    }

    public function withProtocolVersion($version)
    {
        return new self($this->delegate->withProtocolVersion($version), $this->context);
    }

    public function getHeaders()
    {
        return $this->delegate->getHeaders();
    }

    public function hasHeader($name)
    {
        return $this->delegate->hasHeader($name);
    }

    public function getHeader($name)
    {
        return $this->delegate->getHeader($name);
    }

    public function getHeaderLine($name)
    {
        return $this->delegate->getHeaderLine($name);
    }

    public function withHeader($name, $value)
    {
        return new self($this->delegate->withHeader($name, $value), $this->context);
    }

    public function withAddedHeader($name, $value)
    {
        return new self($this->delegate->withAddedHeader($name, $value), $this->context);
    }

    public function withoutHeader($name)
    {
        return new self($this->delegate->withoutHeader($name), $this->context);
    }
    public function getBody()
    {
        return $this->delegate->getBody();
    }

    public function withBody(StreamInterface $body)
    {
        return new self($this->delegate->withBody($body), $this->context);
    }
}
