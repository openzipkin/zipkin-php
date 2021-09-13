<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http;

/**
 * Abstract response type used for parsing and sampling of http clients and servers.
 *
 * @internal
 */
abstract class Response
{
    /**
     * The request that initiated this response or {@code null} if unknown.
     *
     * @return Request|null not declared on purpose so client/server responses can
     * declare its own type.
     */
    abstract public function getRequest();

    /**
     * The HTTP status code or zero if unreadable.
     *
     * <p>Conventionally associated with the key "http.status_code"
     */
    abstract public function getStatusCode(): int;

    /**
     * @return object|null the underlying response object or {@code null} if there is none.
     */
    abstract public function unwrap(): ?object;
}
