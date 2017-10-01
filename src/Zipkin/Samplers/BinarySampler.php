<?php

namespace Zipkin\Samplers;

use Zipkin\Sampler;

final class BinarySampler implements Sampler
{
    /**
     * @var bool
     */
    private $isSampled;

    private function __construct($isSampled)
    {
        $this->isSampled = $isSampled;
    }

    public static function createAsAlwaysSample()
    {
        return new self(true);
    }

    public static function createAsNeverSample()
    {
        return new self(false);
    }

    /**
     * {@inheritdoc}
     */
    public function isSampled($traceId)
    {
        return $this->isSampled;
    }
}
