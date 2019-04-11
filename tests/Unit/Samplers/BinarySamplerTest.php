<?php

namespace ZipkinTests\Unit\Samplers;

use PHPUnit\Framework\TestCase;
use Zipkin\Samplers\BinarySampler;

final class BinarySamplerTest extends TestCase
{
    public function testAlwaysSample()
    {
        $sampler = BinarySampler::createAsAlwaysSample();
        $this->assertTrue($sampler->isSampled('1'));
    }

    public function testNeverSample()
    {
        $sampler = BinarySampler::createAsNeverSample();
        $this->assertFalse($sampler->isSampled('1'));
    }
}
