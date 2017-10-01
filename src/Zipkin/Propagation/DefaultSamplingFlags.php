<?php

namespace Zipkin\Propagation;

use InvalidArgumentException;

final class DefaultSamplingFlags implements SamplingFlags
{
    /**
     * @var bool|null
     */
    private $sampled;

    /**
     * @var bool
     */
    private $debug;

    private function __construct($sampled, $debug)
    {
        $this->sampled = $sampled;
        $this->debug = $debug;
    }

    /**
     * @param bool $sampled
     * @param bool $debug
     * @throws InvalidArgumentException
     * @return DefaultSamplingFlags
     */
    public static function create($sampled, $debug = false)
    {
        if ($sampled !== null && $sampled !== (bool) $sampled) {
            throw new InvalidArgumentException(sprintf('sampled should be boolean, got %s', gettype($sampled)));
        }

        if ($debug !== (bool) $debug) {
            throw new InvalidArgumentException(sprintf('debug should be boolean, got %s', gettype($debug)));
        }

        return new self($sampled, $debug);
    }

    /**
     * @return DefaultSamplingFlags
     */
    public static function createAsEmpty()
    {
        return new self(null, false);
    }

    /**
     * @return DefaultSamplingFlags
     */
    public static function createAsSampled()
    {
        return new self(true, false);
    }

    /**
     * @return DefaultSamplingFlags
     */
    public static function createAsNotSampled()
    {
        return new self(false, false);
    }

    /**
     * @return DefaultSamplingFlags
     */
    public static function createAsDebug()
    {
        return new self(true, true);
    }

    /**
     * @return bool|null
     */
    public function isSampled()
    {
        return $this->sampled;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->debug;
    }
}
