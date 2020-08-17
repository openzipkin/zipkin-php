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

namespace ZipkinTests\Unit\Propagation;

use Zipkin\Propagation\DefaultSamplingFlags;
use PHPUnit\Framework\TestCase;

final class DefaultSamplingFlagsTest extends TestCase
{
    public function testSamplingFlagsAreCreatedSuccessfully()
    {
        $samplingFlags0 = DefaultSamplingFlags::create(null, false);
        $this->assertNull($samplingFlags0->isSampled());
        $this->assertFalse($samplingFlags0->isDebug());

        $samplingFlags1 = DefaultSamplingFlags::create(true, false);
        $this->assertTrue($samplingFlags1->isSampled());
        $this->assertFalse($samplingFlags1->isDebug());

        $samplingFlags2 = DefaultSamplingFlags::create(false, false);
        $this->assertFalse($samplingFlags2->isSampled());
        $this->assertFalse($samplingFlags2->isDebug());
    }

    public function testDebugOverridesSamplingDecision()
    {
        $samplingFlags = DefaultSamplingFlags::create(false, true);
        $this->assertTrue($samplingFlags->isSampled());
    }
}
