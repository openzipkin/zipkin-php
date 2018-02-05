<?php

namespace ZipkinTests\Unit\Samplers;

use PHPUnit_Framework_TestCase;
use Zipkin\Samplers\BinarySampler;

final class BinarySamplerTest extends PHPUnit_Framework_TestCase
{
    public function testAlwaysSample()
    {
        $sampler = BinarySampler::createAsAlwaysSample();
        $this->assertTrue($sampler->isSampled(1));
    }

    public function testNeverSample()
    {
        $sampler = BinarySampler::createAsNeverSample();
        $this->assertFalse($sampler->isSampled(1));
    }
}
