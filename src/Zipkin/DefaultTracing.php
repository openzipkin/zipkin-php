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

use Zipkin\Propagation\B3;
use Zipkin\Propagation\CurrentTraceContext;
use Zipkin\Propagation\Propagation;
use Zipkin\Reporter;

final class DefaultTracing implements Tracing
{
    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * @var Propagation
     */
    private $propagation;

    /**
     * @var bool
     */
    private $isNoop;

    /**
     * @param Endpoint $localEndpoint
     * @param Reporter $reporter
     * @param Sampler $sampler
     * @param bool $usesTraceId128bits
     * @param CurrentTraceContext $currentTraceContext
     * @param bool $isNoop
     */
    public function __construct(
        Endpoint $localEndpoint,
        Reporter $reporter,
        Sampler $sampler,
        $usesTraceId128bits,
        CurrentTraceContext $currentTraceContext,
        bool $isNoop,
        Propagation $propagation,
        bool $supportsJoin,
        bool $alwaysReportSpans = false
    ) {
        $this->tracer = new Tracer(
            $localEndpoint,
            $reporter,
            $sampler,
            $usesTraceId128bits,
            $currentTraceContext,
            $isNoop,
            $supportsJoin,
            $alwaysReportSpans
        );

        $this->propagation = $propagation;
        $this->isNoop = $isNoop;
    }

    /**
     * @return Tracer
     */
    public function getTracer(): Tracer
    {
        return $this->tracer;
    }

    /**
     * @return Propagation
     */
    public function getPropagation(): Propagation
    {
        return $this->propagation;
    }

    /**
     * When true, no recording is done and nothing is reported to zipkin. However, trace context is
     * still injected into outgoing requests.
     *
     * @return bool
     * @see Span#isNoop()
     */
    public function isNoop(): bool
    {
        return $this->isNoop;
    }
}
