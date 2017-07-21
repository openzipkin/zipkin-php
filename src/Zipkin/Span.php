<?php

namespace Zipkin;

use InvalidArgumentException;

final class Span
{
    /**
     * Span name in lowercase, rpc method for example. Conventionally, when the
     * span name isn't known, name = "unknown".
     *
     * @var string
     */
    private $name;

    /**
     * Epoch microseconds of the start of this span, absent if this an incomplete
     * span.
     *
     * @var int
     */
    private $startTimestamp;

    /**
     * Measurement in microseconds of the critical path, if known. Durations of
     * less than one microsecond must be rounded up to 1 microsecond.
     *
     * @var int
     */
    private $duration;

    private function __construct($name, $startTimestamp)
    {
        $this->name = $name;
        $this->startTimestamp = $startTimestamp;
    }

    /**
     * @param string $name the name of the span
     * @param array $options a key => value map of options
     * @return Span
     */
    public static function create($name, array $options = [])
    {
        if (!is_string($name) || empty($name)) {
            throw new InvalidArgumentException(
                sprintf('Non empty string name is expected, got \'%s\'', $name)
            );
        }

        if (isset($options['start_timestamp'])) {
            if (!Timestamp\is_valid_timestamp($options['start_timestamp'])) {
                throw new InvalidArgumentException(
                    sprintf('Valid microtime expected, got \'%s\'', $options['start_timestamp'])
                );
            }

            $startTimestamp = $options['start_timestamp'];
        } else {
            $startTimestamp = Timestamp\now();
        }

        return new self($name, $startTimestamp);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return float
     */
    public function getStartTimestamp()
    {
        return $this->startTimestamp;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    public function finish()
    {
        $now = Timestamp\now();
        $this->duration = ($now - $this->startTimestamp);
    }
}
