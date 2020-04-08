<?php

namespace Zipkin\Samplers;

use InvalidArgumentException;
use Zipkin\Sampler;

final class PercentageSampler implements Sampler
{
    /**
     * @var float
     */
    private $rate;

    private function __construct($rate)
    {
        $this->rate = $rate;
    }

    /**
     * @param float $rate
     * @return PercentageSampler
     * @throws InvalidArgumentException
     */
    public static function create($rate)
    {
        if (!is_float($rate)) {
            throw new InvalidArgumentException(
                sprintf('Invalid rate. Expect a float value, got %s', gettype($rate))
            );
        }

        if ($rate > 1 || $rate < 0) {
            throw new InvalidArgumentException(
                sprintf('Invalid rate. Expected a value between 0 and 1, got %f', $rate)
            );
        }
        return new self($rate);
    }

    /**
     * {@inheritdoc}
     */
    public function isSampled($traceId)
    {
        return (mt_rand(0, 99) / 100) < $this->rate;
    }
}
