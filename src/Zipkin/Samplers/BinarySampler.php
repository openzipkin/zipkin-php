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

    public static function createAsAlwaysSample(): self
    {
        return new self(true);
    }

    public static function createAsNeverSample(): self
    {
        return new self(false);
    }

    /**
     * {@inheritdoc}
     */
    public function isSampled(string $traceId): bool
    {
        return $this->isSampled;
    }
}
