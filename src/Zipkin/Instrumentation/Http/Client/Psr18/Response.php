<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client\Psr18;

use Zipkin\Instrumentation\Http\Client\Response as ClientResponse;
use Zipkin\Instrumentation\Http\Client\Request as ClientRequest;
use Psr\Http\Message\ResponseInterface;

final class Response extends ClientResponse
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
    public function getRequest(): ?ClientRequest
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
