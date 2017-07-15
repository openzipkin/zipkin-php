<?php

namespace Zipkin;

final class Annotation
{
    private $value;
    private $timestamp;

    private function __construct($value, $timestamp)
    {
        $this->value = $value;
        $this->timestamp = $timestamp;
    }

    /**
     * @param mixed $value
     * @param float $timestamp
     * @return Annotation
     */
    public static function create($value, $timestamp)
    {
        if (!Timestamp\is_valid_timestamp($timestamp)) {
            throw new \InvalidArgumentException(
                sprintf('Valid timestamp represented microtime expected, got \'%s\'', $timestamp)
            );
        }

        return new self($value, $timestamp);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return float
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
