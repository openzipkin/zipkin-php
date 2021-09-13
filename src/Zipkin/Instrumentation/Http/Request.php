<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http;

use const Zipkin\Tags\HTTP_URL;
use const Zipkin\Tags\HTTP_PATH;
use const Zipkin\Tags\HTTP_METHOD;

/**
 * Abstract request type used for parsing and sampling of http clients and servers.
 *
 * @internal
 */
abstract class Request
{
    /**
     * The HTTP method, or verb, such as "GET" or "POST".
     *
     * <p>Conventionally associated with the key "http.method"
     *
     * <h3>Note</h3>
     * <p>It is part of the <a href="https://tools.ietf.org/html/rfc7231#section-4.1">HTTP RFC</a>
     * that an HTTP method is case-sensitive. Do not downcase results.
     *
     * @see HTTP_METHOD
     */
    abstract public function getMethod(): string;

    /**
     * The absolute http path, without any query parameters. Ex. "/objects/abcd-ff"
     *
     * <p>Conventionally associated with the key "http.path"
     *
     * <p>{@code null} could mean not applicable to the HTTP method (ex CONNECT).
     *
     * <h3>Implementation notes</h3>
     * Some HTTP client abstractions, return the input as opposed to
     * the absolute path. One common problem is a path requested as "", not "/". When that's the case,
     * normalize "" to "/". This ensures values are consistent with wire-level clients and behaviour
     * consistent with RFC 7230 Section 2.7.3.
     *
     * @see HTTP_PATH
     */
    abstract public function getPath(): ?string;

    /**
     * The entire URL, including the scheme, host and query parameters if available.
     *
     * <p>Conventionally associated with the key "http.url"
     *
     * @see HTTP_URL
     */
    abstract public function getUrl(): string;

    /**
     * Returns one value corresponding to the specified header, or null.
     */
    abstract public function getHeader(string $name): ?string;

    /**
     * @return object|null the underlying request object or {@code null} if there is none.
     */
    abstract public function unwrap(): ?object;
}
