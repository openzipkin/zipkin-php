<?php

namespace ZipkinBundle;

use Zipkin\Instrumentation\Http\Server\Response as ServerResponse;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

class Response extends ServerResponse
{
    /**
     * @var HttpFoundationResponse
     */
    private $delegate;

    public function __construct(HttpFoundationResponse $delegate)
    {
        $this->delegate = $delegate;
    }

    public function getStatusCode(): int
    {
        return $this->delegate->getStatusCode();
    }

    /**
     * @return HttpFoundationRequest
     */
    public function unwrap()
    {
        return $this->delegate;
    }
}
