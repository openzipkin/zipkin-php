<?php

declare(strict_types=1);

namespace Zipkin\Reporters\Http;

interface ClientFactory
{
    /**
     * @param array $options the options for HTTP call:
     *
     * <code>
     * $options = [
     *   'endpoint_url' => 'http://myzipkin:9411/api/v2/spans', // the reporting url for zipkin server
     *   'headers'      => ['X-API-Key' => 'abc123'] // the additional headers to be included in the request
     *   'timeout'      => 10, // the timeout for the request in seconds
     * ];
     * </code>
     *
     * @return callable(string):void
     */
    public function build(array $options): callable;
}
