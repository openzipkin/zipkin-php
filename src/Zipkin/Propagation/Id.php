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

namespace Zipkin\Propagation\Id;

/**
 * @return string
 */
function generateTraceIdWith128bits(): string
{
    return \bin2hex(\openssl_random_pseudo_bytes(16));
}

/**
 * @return string
 */
function generateNextId(): string
{
    return \bin2hex(\openssl_random_pseudo_bytes(8));
}

/**
 * @param string $value
 * @return bool
 */
function isValidTraceId(string $value): bool
{
    return \ctype_xdigit($value) &&
        \strlen($value) > 0 && \strlen($value) <= 32;
}

/**
 * @param string $value
 * @return bool
 */
function isValidSpanId(string $value): bool
{
    return \ctype_xdigit($value) &&
        \strlen($value) > 0 && \strlen($value) <= 16;
}
