<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server\Psr15;

use Zipkin\Instrumentation\Http\Server\Response as ServerResponse;
use Zipkin\Instrumentation\Http\Request;
use Psr\Http\Message\ResponseInterface;

final class Response implements ServerResponse
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
    public function getRequest(): ?Request
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
        return null;
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
