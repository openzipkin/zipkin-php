<?php

declare(strict_types=1);

namespace Zipkin\Reporters;

use Zipkin\Recording\ReadbackSpan;
use Zipkin\ErrorParser;
use Zipkin\Endpoint;
use Zipkin\DefaultErrorParser;

class JsonV2Serializer implements SpanSerializer
{
    private ErrorParser $errorParser;

    public function __construct(ErrorParser $errorParser = null)
    {
        $this->errorParser = $errorParser ?? new DefaultErrorParser();
    }

    /**
     * @param ReadbackSpan[]|array $spans
     */
    public function serialize(array $spans): string
    {
        $spansAsArray = array_map([self::class, 'serializeSpan'], $spans);

        return '[' . implode(',', $spansAsArray) . ']';
    }

    private static function serializeEndpoint(Endpoint $endpoint): string
    {
        $endpointStr =  '{"serviceName":"' . \strtolower(self::escapeString($endpoint->getServiceName())) . '"';

        if ($endpoint->getIpv4() !== null) {
            $endpointStr .= ',"ipv4":"' . $endpoint->getIpv4() . '"';
        }

        if ($endpoint->getIpv6() !== null) {
            $endpointStr .= ',"ipv6":"' . $endpoint->getIpv6() . '"';
        }

        if ($endpoint->getPort() !== null) {
            $endpointStr .= ',"port":' . $endpoint->getPort();
        }

        return $endpointStr . '}';
    }

    private static function escapeString(string $s): string
    {
        $encodedString = \json_encode($s);
        return $encodedString === false ? $s : \mb_substr($encodedString, 1, -1);
    }

    private function serializeSpan(ReadbackSpan $span): string
    {
        $spanStr =
            '{"id":"' . $span->getSpanId() . '"'
            . ',"traceId":"' . $span->getTraceId() . '"'
            . ',"timestamp":' . $span->getTimestamp();

        if ($span->getName() !== null) {
            $spanStr .= ',"name":"' . \strtolower(self::escapeString($span->getName())) . '"';
        }

        if ($span->getDuration() !== null) {
            $spanStr .= ',"duration":' . $span->getDuration();
        }

        if (null !== ($localEndpoint = $span->getLocalEndpoint())) {
            $spanStr .= ',"localEndpoint":' . self::serializeEndpoint($localEndpoint);
        }

        if ($span->getParentId() !== null) {
            $spanStr .= ',"parentId":"' . $span->getParentId() . '"';
        }

        if ($span->isDebug()) {
            $spanStr .= ',"debug":true';
        }

        if ($span->isShared()) {
            $spanStr .= ',"shared":true';
        }

        if ($span->getKind() !== null) {
            $spanStr .= ',"kind":"' . $span->getKind() . '"';
        }

        if (null !== ($remoteEndpoint = $span->getRemoteEndpoint())) {
            $spanStr .= ',"remoteEndpoint":' . self::serializeEndpoint($remoteEndpoint);
        }

        if (!empty($span->getAnnotations())) {
            $spanStr .= ',"annotations":[';
            $firstIteration = true;
            foreach ($span->getAnnotations() as $annotation) {
                if ($firstIteration) {
                    $firstIteration = false;
                } else {
                    $spanStr .= ',';
                }
                $spanStr .= '{"value":"' . self::escapeString($annotation['value'])
                    . '","timestamp":' . $annotation['timestamp'] . '}';
            }
            $spanStr .= ']';
        }

        if ($span->getError() === null) {
            $tags = $span->getTags();
        } else {
            $tags = $span->getTags() + $this->errorParser->parseTags($span->getError());
        }

        if (!empty($tags)) {
            $spanStr .= ',"tags":{';
            $firstIteration = true;
            foreach ($tags as $key => $value) {
                if ($firstIteration) {
                    $firstIteration = false;
                } else {
                    $spanStr .= ',';
                }
                $spanStr .= '"' . $key . '":"' . self::escapeString($value) . '"';
            }
            $spanStr .= '}';
        }

        return $spanStr . '}';
    }
}
