<?php

namespace Zipkin;

use InvalidArgumentException;
use Zipkin\Timestamp;

final class Annotation
{
    const WIRE_SEND = 'ws';
    const WIRE_RECV = 'wr';
    const LOCAL_COMPONENT = 'lc';
    const ERROR = 'error';

    /**
     * @var string
     */
    private $value;

    /**
     * @var float
     */
    private $timestamp;

    private function __construct($value, $timestamp)
    {
        $this->value = $value;
        $this->timestamp = $timestamp;
    }

    /**
     * @param string $value
     * @param int $timestamp
     * @throws InvalidArgumentException on empty or not stringable value or invalid timestamp
     * @return Annotation
     */
    public static function create($value, $timestamp)
    {
        if (empty($value) || !is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
            throw new InvalidArgumentException('Invalid annotation value');
        }

        if (!Timestamp\is_valid_timestamp($timestamp)) {
            throw new InvalidArgumentException(
                sprintf('Valid timestamp represented microtime expected, got \'%s\'', $timestamp)
            );
        }

        return new self($value, $timestamp);
    }

    /**
     * @return string
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

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'value' => $this->value,
            'timestamp' => $this->timestamp,
        ];
    }
}
