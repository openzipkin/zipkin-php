<?php

namespace Zipkin;

use InvalidArgumentException;
use Zipkin\Timestamp;

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

    const MESSAGE_SEND = 'ms';
    const MESSAGE_RECV = 'mr';
    const WIRE_SEND = 'ws';
    const WIRE_RECV = 'wr';
    const CLIENT_SEND_FRAGMENT = 'csf';
    const CLIENT_RECV_FRAGMENT = 'crf';
    const SERVER_SEND_FRAGMENT = 'ssf';
    const SERVER_RECV_FRAGMENT = 'srf';
    const LOCAL_COMPONENT = 'lc';
    const ERROR = 'error';
    const CLIENT_ADDR = 'ca';
    const SERVER_ADDR = 'sa';
    const MESSAGE_ADDR = 'ma';

    const CORE_ANNOTATIONS = ['cs', 'cr', 'ss', 'sr', 'ws', 'wr', 'csf', 'crf', 'ssf', 'srf'];

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
     * @param float $timestamp
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
     * @return array|scalar[string]
     */
    public function toArray()
    {
        return [
            'value' => $this->value,
            'timestamp' => $this->timestamp,
        ];
    }
}
