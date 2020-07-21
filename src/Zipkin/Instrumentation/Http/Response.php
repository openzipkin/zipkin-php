<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http;

abstract class Response
{
    /**
     * The request that initiated this response or {@code null} if unknown.
     */
    public function getRequest(): ?Request
    {
        return null;
    }

    /**
     * The HTTP status code or zero if unreadable.
     *
     * <p>Conventionally associated with the key "http.status_code"
     */
    abstract public function getStatusCode(): int;

    /**
     * @return mixed the underlying response object or {@code null} if there is none.
     */
    abstract public function unwrap();
}
