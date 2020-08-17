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

namespace Zipkin\Reporters;

use Zipkin\Reporter;
use Zipkin\Recording\Span;

final class InMemory implements Reporter
{
    /**
     * @var array|Span[]
     */
    private $spans = [];

    public function report(array $spans): void
    {
        $this->spans = \array_merge($this->spans, $spans);
    }

    /**
     * @return array|Span[]
     */
    public function flush(): array
    {
        $spans = $this->spans;
        $this->spans = [];
        return $spans;
    }
}
