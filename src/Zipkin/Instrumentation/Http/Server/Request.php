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

namespace Zipkin\Instrumentation\Http\Server;

use Zipkin\Instrumentation\Http\Request as HttpRequest;
use const Zipkin\Tags\HTTP_ROUTE;

abstract class Request extends HttpRequest
{
    /**
     * Returns an expression such as "/items/:itemId" representing an application endpoint,
     * conventionally associated with the tag key "http.route". If no route matched, "" (empty string)
     * is returned. Null indicates this instrumentation doesn't understand http routes.
     *
     * <p>The route is associated with the request, but it may not be visible until response
     * processing. The reasons is that many server implementations process the request before they can
     * identify the route. Parsing should expect this and look at {@link HttpResponse#route()} as
     * needed.
     *
     * @see HTTP_ROUTE
     */
    public function getRoute(): ?string
    {
        return null;
    }
}
