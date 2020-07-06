<?php

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
