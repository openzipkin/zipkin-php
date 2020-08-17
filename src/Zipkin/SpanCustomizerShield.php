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
 * SpanCustomizerShield is a simple implementation of SpanCustomizer.
 * It is highly recommended to not to wrap a NOOP span as it will only
 * add overhead for no benefit.
 */
final class SpanCustomizerShield implements SpanCustomizer
{
    /**
     * @var Span
     */
    private $delegate;

    public function __construct(Span $span)
    {
        $this->delegate = $span;
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): void
    {
        $this->delegate->setName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function tag(string $key, string $value): void
    {
        $this->delegate->tag($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function annotate(string $value, int $timestamp = null): void
    {
        $this->delegate->annotate($value, $timestamp);
    }
}
