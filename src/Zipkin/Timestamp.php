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

namespace Zipkin\Timestamp;

/**
 * Returns current timestamp in the zipkin format.
 *
 * @return int
 */
function now(): int
{
    return (int) (\microtime(true) * 1000 * 1000);
}

/**
 * Checks whether a timestamp is valid or not.
 *
 * @param int $timestamp
 * @return bool
 */
function isValid(int $timestamp): bool
{
    return \strlen((string) $timestamp) === 16;
}
