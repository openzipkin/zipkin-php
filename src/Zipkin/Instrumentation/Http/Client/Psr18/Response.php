<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Client\Psr18;

use Zipkin\Instrumentation\Http\Client\Response as ClientResponse;
use Zipkin\Instrumentation\Http\Client\Request as ClientRequest;
use Psr\Http\Message\ResponseInterface;

final class Response extends ClientResponse
{
    /**
     * @var ResponseInterface
     */
    private $delegate;

    /**
     * @var Request|null
     */
    private $request;

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
     *
     * @return ResponseInterface
     */
    public function unwrap()
    {
        return $this->delegate;
    }
}
