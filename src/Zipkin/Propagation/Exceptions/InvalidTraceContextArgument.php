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

namespace Zipkin\Propagation\Exceptions;

use InvalidArgumentException;

final class InvalidTraceContextArgument extends InvalidArgumentException
{
    public static function forTraceId(string $traceId): self
    {
        return new self(\sprintf('Invalid trace id, got %s', $traceId));
    }

    public static function forSpanId(string $spanId): self
    {
        return new self(\sprintf('Invalid span id, got %s', $spanId));
    }

    public static function forParentSpanId(string $parentId): self
    {
        return new self(\sprintf('Invalid parent span id, got %s', $parentId));
    }

    public static function forSampling(string $value): self
    {
        return new self(\sprintf('Invalid sampling value, got %s, expected 1, 0 or d', $value));
    }
}
