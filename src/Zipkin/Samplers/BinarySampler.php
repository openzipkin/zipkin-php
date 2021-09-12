<?php

declare(strict_types=1);

namespace Zipkin\Samplers;

use Zipkin\Sampler;

final class BinarySampler implements Sampler
{
    private bool $isSampled;

    private function __construct(bool $isSampled)
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
