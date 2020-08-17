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

final class DefaultSamplingFlags implements SamplingFlags
{
    /**
     * @var bool|null
     */
    private $isSampled;

    /**
     * @var bool
     */
    private $isDebug;

    private function __construct(?bool $isSampled, bool $isDebug)
    {
        $this->isSampled = $isDebug ?: $isSampled;
        $this->isDebug = $isDebug;
    }

    public static function create(?bool $isSampled, bool $isDebug = false): self
    {
        return new self($isSampled, $isDebug);
    }

    public static function createAsEmpty(): self
    {
        return new self(SamplingFlags::EMPTY_SAMPLED, SamplingFlags::EMPTY_DEBUG);
    }

    public static function createAsSampled(): self
    {
        return new self(true, false);
    }

    public static function createAsNotSampled(): self
    {
        return new self(false, false);
    }

    public static function createAsDebug(): self
    {
        return new self(true, true);
    }

    public function isSampled(): ?bool
    {
        return $this->isSampled;
    }

    public function isDebug(): bool
    {
        return $this->isDebug;
    }

    public function isEmpty(): bool
    {
        return $this->isSampled === self::EMPTY_SAMPLED &&
            $this->isDebug === self::EMPTY_DEBUG;
    }

    public function isEqual(SamplingFlags $samplingFlags): bool
    {
        return $this->isDebug === $samplingFlags->isDebug()
            && $this->isSampled === $samplingFlags->isSampled();
    }

    /**
     * @return DefaultSamplingFlags
     */
    public function withSampled(bool $isSampled): SamplingFlags
    {
        return new self($isSampled, false);
    }
}
