<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server\Psr15;

use Zipkin\Instrumentation\Http\Server\Response as ServerResponse;
use Zipkin\Instrumentation\Http\Server\Request as ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class Response extends ServerResponse
{
    /**
     * @var ResponseInterface
     */
    private $delegate;

    /**
     * @var Request|null
     */
    private $request;

    /**
     * @var string|null
     */
    private $route;

    public function __construct(
        ResponseInterface $delegate,
        ?Request $request = null,
        ?string $route = null
    ) {
        $this->delegate = $delegate;
        $this->request = $request;
        $this->route = $route;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest(): ?ServerRequest
    {
        return $this->request;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode(): int
    {
        return $this->delegate->getStatusCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getRoute(): ?string
    {
        return $this->route;
    }

    /**
     * {@inheritdoc}
     */
    public function unwrap(): ResponseInterface
    {
        return $this->delegate;
    }
}
