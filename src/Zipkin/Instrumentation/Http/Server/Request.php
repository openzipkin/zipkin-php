<?php

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server;

use Zipkin\Instrumentation\Http\Request as HttpRequest;

interface Request extends HttpRequest
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
     * @see Tags\HTTP_ROUTE
     */
    public function getRoute(): ?string;
}
