<?php

declare(strict_types=1);

namespace Zipkin\Samplers;

use Zipkin\Sampler;
use InvalidArgumentException;

final class PercentageSampler implements Sampler
{
    private float $rate;

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
