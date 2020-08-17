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

namespace Zipkin\Recording;

use Zipkin\Reporters\SpanSerializer;
use Zipkin\ErrorParser;
use Zipkin\Endpoint;
use Throwable;

/**
 * ReadbackSpan is an interface for accessing the recording
 * span without the possibility to mutate it.
 */
interface ReadbackSpan
{
    public function getSpanId(): string;
    public function getTraceId(): string;
    public function getParentId(): ?string;
    public function isDebug(): bool;
    public function isShared(): bool;
    public function getName(): ?string;
    public function getKind(): ?string;
    public function getTimestamp(): int;
    public function getDuration(): ?int;
    public function getLocalEndpoint(): ?Endpoint;
    public function getTags(): array;
    public function getAnnotations(): array;
    public function getError(): ?Throwable;
    public function getRemoteEndpoint(): ?Endpoint;
}
