<?php

namespace Zipkin;

use InvalidArgumentException;

final class Span
{
    private $name;

    private function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $name the name of the span
     * @param array $options a key => value map of options
     * @return Span
     */
    public static function create($name, $options = [])
    {
        if (!is_string($name) || empty($name)) {
            throw new InvalidArgumentException(
                sprintf('Non empty string name is expected, got \'%s\'', $name)
            );
        }

        if (isset($options['start_timestamp']) && !Timestamp\is_valid_timestamp($options['start_timestamp'])) {
            throw new InvalidArgumentException(
                sprintf('Valid microtime expected, got \'%s\'', $options['start_timestamp'])
            );
        }

        return new self($name);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
