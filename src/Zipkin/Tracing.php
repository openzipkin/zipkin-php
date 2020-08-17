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

namespace Zipkin;

use Zipkin\Propagation\Propagation;

interface Tracing
{
    /**
     * All tracing commands start with a {@link Span}. Use a tracer to create spans.
     *
     * @return Tracer
     */
    public function getTracer(): Tracer;

    /**
     * When a trace leaves the process, it needs to be propagated, usually via headers. This utility
     * is used to inject or extract a trace context from remote requests.
     *
     * @return Propagation
     */
    public function getPropagation(): Propagation;

    /**
     * When true, no recording is done and nothing is reported to zipkin. However, trace context is
     * still injected into outgoing requests.
     *
     * @return bool
     * @see Span#isNoop()
     */
    public function isNoop(): bool;
}
