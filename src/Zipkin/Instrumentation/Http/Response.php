<?php

/**
 * Copyright 2020 OpenZipkin Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
     * @return mixed the underlying response object or {@code null} if there is none.
     */
    abstract public function unwrap();
}
