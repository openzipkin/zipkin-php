<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http;

interface Response
{
    /**
     * The request that initiated this response or {@code null} if unknown.
     */
    public function getRequest(): ?Request;

    /**
     * The HTTP status code or zero if unreadable.
     *
     * <p>Conventionally associated with the key "http.status_code"
     *
     * @since 5.10
     */
    public function getStatusCode(): int;

    /**
     * @return mixed the underlying response object or {@code null} if there is none.
     */
    public function unwrap();
}
