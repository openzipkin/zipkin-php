<?php

namespace Zipkin\Recording;

use Zipkin\Annotation;
use Zipkin\Endpoint;
use Zipkin\TraceContext;

final class Span
{
    private $timestamp;
    private $finished = false;
    private $name;
    private $kind;
    private $traceId;
    private $parentId;
    private $spanId;
    private $debug;
    private $sampled;
    private $annotations;
    private $tags;
    private $duration;
    private $remoteEndpoint;

    private function __construct($traceId, $parentId, $spanId, $debug, $sampled, Endpoint $endpoint)
    {
        $this->traceId = $traceId;
        $this->parentId = $parentId;
        $this->spanId = $spanId;
        $this->debug = $debug;
        $this->sampled = $sampled;
        $this->endpoint = $endpoint;
    }

    /**
     * @param TraceContext $context
     * @param Endpoint $endpoint
     * @return Span
     */
    public static function createFromContext(TraceContext $context, Endpoint $endpoint)
    {
        return new self(
            $context->getTraceId(),
            $context->getParentId(),
            $context->getSpanId(),
            $context->debug(),
            $context->getSampled(),
            $endpoint
        );
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function start($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $kind
     * @return void
     */
    public function setKind($kind)
    {
        $this->kind = $kind;
    }

    public function annotate($timestamp, $value)
    {
        $this->annotations[] = Annotation::create($value, $timestamp);
    }

    public function tag($key, $value)
    {
        $this->tags[$key] = $value;
    }

    public function setRemoteEndpoint(Endpoint $remoteEndpoint)
    {
        $this->remoteEndpoint = $remoteEndpoint;
    }

    /**
     * Completes and reports the span
     *
     * @param null $finishTimestamp
     */
    public function finish($finishTimestamp = null)
    {
        if ($this->finished) {
            return;
        }

        if ($this->timestamp !== null && $finishTimestamp !== null) {
            $this->duration = $finishTimestamp - $this->timestamp;
        }
    }

    public function toArray()
    {
        $endpoint = $this->endpoint;

        return [
            'id' => (string) $this->spanId,
            'name' => $this->name,
            'traceId' => (string) $this->traceId,
            'parentId' => $this->parentId ? (string) $this->parentId : null,
            'timestamp' => $this->timestamp,
            'duration' => $this->duration,
            'debug' => $this->debug,
            'annotations' => array_map(
                function(Annotation $annotation) use ($endpoint) {
                    return $annotation->toArray() + ['endpoint' => $endpoint->toArray()];
                },
                $this->annotations
            ),
            'binaryAnnotations' => array_map(
                function($key, $value) use ($endpoint) {
                    return [
                        'key' => $key,
                        'value' => $value,
                        'endpoint' => $endpoint->toArray()
                    ];
                },
                array_keys($this->tags),
                $this->tags
            ),
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {

    }
}
