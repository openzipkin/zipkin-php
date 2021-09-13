<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server\Psr15;

use Zipkin\Instrumentation\Http\Server\Response as ServerResponse;
use Zipkin\Instrumentation\Http\Server\Request as ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class Response extends ServerResponse
{
    private ResponseInterface $delegate;

    private ?Request $request;

    public function __construct(
        ResponseInterface $delegate,
        ?Request $request = null
    ) {
        $this->delegate = $delegate;
        $this->request = $request;
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
    public function unwrap(): ResponseInterface
    {
        return $this->delegate;
    }
}
