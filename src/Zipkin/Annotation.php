<?php

namespace Zipkin;

use InvalidArgumentException;

final class Annotation
{
    /**
     * The client has made the request. This sets the beginning of the span.
     */
    const CLIENT_START = 'cs';

    /**
     * The server has received the request and will start processing it. The difference between this and cs will be
     * combination of network latency and clock jitter.
     */
    const SERVER_RECEIVE = 'sr';

    /**
     * The server has completed processing and has sent the request back to the client. The difference between this
     * and sr will be the amount of time it took the server to process the request.
     */
    const SERVER_SEND = 'ss';

    /**
     * The client has received the response from the server. This sets the end of the span. The RPC is considered
     * complete when this annotation is recorded.
     */
    const CLIENT_RECEIVE = 'cr';

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
        if (!is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
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
