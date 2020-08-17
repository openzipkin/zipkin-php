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
 * Simple interface users can customize a span with. For example, this can add custom tags useful in
 * looking up spans.
 *
 * <p>This type is safer to expose directly to users than {@link Span}, as it has no hooks that
 * can affect the span lifecycle.
 */
interface SpanCustomizer
{
    /**
     * Sets the string name for the logical operation this span represents.
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name): void;

    /**
     * Tags give your span context for search, viewing and analysis. For example, a key
     * "your_app.version" would let you lookup spans by version. A tag {@link Zipkin\Tags\SQL_QUERY}
     * isn't searchable, but it can help in debugging when viewing a trace.
     *
     * @param string $key Name used to lookup spans, such as "your_app.version". See {@link Zipkin\Tags} for
     * standard ones.
     * @param string $value value.
     * @return void
     */
    public function tag(string $key, string $value): void;

    /**
     * Associates an event that explains latency with the current system time.
     *
     * @param string $value A short tag indicating the event, like "finagle.retry"
     * @param int|null $timestamp
     * @return void
     * @see Annotations
     */
    public function annotate(string $value, int $timestamp = null): void;
}
