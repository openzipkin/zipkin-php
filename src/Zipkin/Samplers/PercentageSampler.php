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

namespace Zipkin\Samplers;

use InvalidArgumentException;
use Zipkin\Sampler;

final class PercentageSampler implements Sampler
{
    /**
     * @var float
     */
    private $rate;

    private function __construct(float $rate)
    {
        $this->rate = $rate;
    }

    /**
     * @param float $rate
     * @return PercentageSampler
     * @throws InvalidArgumentException
     */
    public static function create(float $rate): self
    {
        if ($rate > 1 || $rate < 0) {
            throw new InvalidArgumentException(
                \sprintf('Invalid rate. Expected a value between 0 and 1, got %f', $rate)
            );
        }
        return new self($rate);
    }

    /**
     * {@inheritdoc}
     */
    public function isSampled(string $traceId): bool
    {
        return (\mt_rand(0, 99) / 100) < $this->rate;
    }
}
