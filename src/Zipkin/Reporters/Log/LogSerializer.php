<?php

declare(strict_types=1);

namespace Zipkin\Reporters\Log;

use Zipkin\Reporters\SpanSerializer;
use Zipkin\Recording\ReadbackSpan;

final class LogSerializer implements SpanSerializer
{
    /**
     * @param ReadbackSpan[]|array $spans
     */
    public function serialize(array $spans): string
    {
        $spansAsArray = array_map([self::class, 'serializeSpan'], $spans);

        return implode(PHP_EOL, $spansAsArray);
    }

    private function serializeSpan(ReadbackSpan $span): string
    {
        $serialized = [];
        $serialized[] = sprintf("Name: %s", $span->getName());
        $serialized[] = sprintf("TraceID: %s", $span->getTraceId());
        $serialized[] = sprintf("SpanID: %s", $span->getSpanId());
        if (!is_null($parentID = $span->getParentId())) {
            $serialized[] = sprintf("StartTime: %s", $parentID);
        }
        $serialized[] = sprintf("Timestamp: %s", $span->getTimestamp());
        $serialized[] = sprintf("Duration: %s", $span->getDuration());
        $serialized[] = sprintf("Kind: %s", $span->getKind());

        $serialized[] = sprintf("LocalEndpoint: %s", $span->getLocalEndpoint()->getServiceName());

        if (\count($tags = $span->getTags()) > 0) {
            $serialized[] = "Tags:";

            foreach ($tags as $key => $value) {
                $serialized[] = sprintf("    %s: %s", $key, $value);
            }
        }

        if (\count($annotations = $span->getAnnotations()) > 0) {
            $serialized[] = "Annotations:";

            foreach ($annotations as $annotation) {
                $serialized[] = sprintf("    - timestamp: %s", $annotation["timestamp"]);
                $serialized[] = sprintf("      value: %s", $annotation["value"]);
            }
        }

        if (!is_null($remoteEndpoint = $span->getRemoteEndpoint())) {
            $serialized[] = sprintf("RemoteEndpoint: %s", $remoteEndpoint->getServiceName());
        }

        return implode(PHP_EOL, $serialized);
    }
}
