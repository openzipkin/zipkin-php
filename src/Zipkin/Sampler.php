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

/**
 * Sampler is responsible for deciding if a particular trace should be "sampled", i.e. whether the
 * overhead of tracing will occur and/or if a trace will be reported to the collection tier.
 *
 * Zipkin v1 uses before-the-fact sampling. This means that the decision to keep or drop the
 * trace is made before any work is measured, or annotations are added. As such, the input parameter
 * to zipkin v1 samplers is the trace ID (lower 64-bits under the assumption all bits are random).
 *
 * The instrumentation sampling decision happens once, at the root of the trace, and is
 * propagated downstream. For this reason, the algorithm needn't be consistent based on trace ID.
 */
interface Sampler
{
    /**
     * Returns true if the trace ID should be measured.
     *
     * @param string $traceId
     * @return bool
     */
    public function isSampled(string $traceId): bool;
}
