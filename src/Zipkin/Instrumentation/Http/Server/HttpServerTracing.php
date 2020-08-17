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

use Zipkin\Tracing;

/**
 * HttpServerTracing includes all the elements needed to trace a HTTP server
 * middleware or request handler.
 */
class HttpServerTracing
{
    /**
     * @var Tracing
     */
    private $tracing;

    /**
     * @var HttpServerParser
     */
    private $parser;

    /**
     * function that decides to sample or not an unsampled
     * request.
     *
     * @var (callable(Request):?bool)|null
     */
    private $requestSampler;

    public function __construct(
        Tracing $tracing,
        HttpServerParser $parser = null,
        callable $requestSampler = null
    ) {
        $this->tracing = $tracing;
        $this->parser = $parser ?? new DefaultHttpServerParser;
        $this->requestSampler = $requestSampler;
    }

    public function getTracing(): Tracing
    {
        return $this->tracing;
    }

    /**
     * @return (callable(Request):?bool)|null
     */
    public function getRequestSampler(): ?callable
    {
        return $this->requestSampler;
    }

    public function getParser(): HttpServerParser
    {
        return $this->parser;
    }
}
