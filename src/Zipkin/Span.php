<?php

namespace Zipkin;

use InvalidArgumentException;

final class Span
{
    private $name;
    private $startTimestamp;

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
}
