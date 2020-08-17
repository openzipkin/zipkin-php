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

/**
 *
 * This class implements the Zipkin\Propagation\Getter interface to extract the zipkin
 * headers out of the $_SERVER variable.
 * Due to the Getter::get signature, $_SERVER gets passed in as $carrier rather
 * than get accessed here directly.
 *
 * Example:
 *   $extractor = $this->tracing->getPropagation()->getExtractor(new ServerHeaders);
 *   $extractedContext = $extractor($_SERVER);
 */
final class ServerHeaders implements Getter
{
    /**
     * {@inheritdoc}
     *
     * @param mixed $carrier
     * @param string $key
     * @return string|null
     */
    public function get($carrier, string $key): ?string
    {
        // Headers in $_SERVER are always uppercased, with any - replaced with an _
        $key = strtoupper($key);
        $key = str_replace('-', '_', $key);

        return $carrier['HTTP_' . $key] ?? null;
    }
}
