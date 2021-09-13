<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server\Psr15;

use Zipkin\Instrumentation\Http\Server\Request as ServerRequest;
use Psr\Http\Message\RequestInterface;

final class Request extends ServerRequest
{
    private RequestInterface $delegate;

    public function __construct(RequestInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return $this->delegate->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): ?string
    {
        return $this->delegate->getUri()->getPath() ?: '/';
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl(): string
    {
        return $this->delegate->getUri()->__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader(string $name): ?string
    {
        return $this->delegate->getHeaderLine($name) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function unwrap(): RequestInterface
    {
        return $this->delegate;
    }
}
