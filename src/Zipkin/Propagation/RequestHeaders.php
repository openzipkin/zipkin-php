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

namespace Zipkin\Propagation;

use Psr\Http\Message\RequestInterface;

class RequestHeaders implements Getter, Setter
{
    /**
     * {@inheritdoc}
     *
     * @param RequestInterface $carrier
     */
    public function get($carrier, string $key): ?string
    {
        // We return the first value becase we relay on the fact that we
        // always override the header value when put method is called.
        return $carrier->hasHeader($key) ? $carrier->getHeader($key)[0] : null;
    }

    /**
     * {@inheritdoc}
     *
     * @param RequestInterface $carrier
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function put(&$carrier, string $key, string $value): void
    {
        $carrier = $carrier->withHeader(\strtolower($key), $value);
    }
}
