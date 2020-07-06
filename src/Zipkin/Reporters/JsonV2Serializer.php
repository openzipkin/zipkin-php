<?php

declare(strict_types=1);

namespace Zipkin\Reporters;

use Zipkin\Recording\ReadbackSpan;
use Zipkin\ErrorParser;
use Zipkin\Endpoint;
use Zipkin\DefaultErrorParser;

class JsonV2Serializer implements SpanSerializer
{
    /**
     * @var ErrorParser
     */
    private $errorParser;

    public function __construct(ErrorParser $errorParser = null)
    {
        $this->errorParser = $errorParser ?? new DefaultErrorParser;
    }

    /**
     * @param ReadbackSpan[]|array $spans
     */
    public function serialize(array $spans): string
    {
        $spansAsArray = array_map([self::class, 'serializeSpan'], $spans);

        return '[' . implode(',', $spansAsArray) . ']';
    }

    private function serializeEndpoint(Endpoint $endpoint): string
    {
        $endpointStr =  '{"serviceName":"' . $endpoint->getServiceName() . '"';

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

    private function serializeSpan(ReadbackSpan $span): string
    {
        $spanStr =
            '{"id":"' . $span->getSpanId() . '"'
            . ',"name":"'. $span->getName() . '"'
            . ',"traceId":"'. $span->getTraceId() . '"'
            . ',"timestamp":'. $span->getTimestamp();

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
            $spanStr .= ',"debug":"'. $span->getKind(). '"';
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
                $spanStr .= '{"value":"' . $annotation['value'] . '","timestamp":' . $annotation['timestamp'] . '}';
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
                $spanStr .= '"'. $key .'":"' . $value . '"';
            }
            $spanStr .= '}';
        }
    
        return $spanStr . '}';
    }
}
