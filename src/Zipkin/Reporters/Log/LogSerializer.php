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

        return implode('\n', $spansAsArray);
    }

    private function serializeSpan(ReadbackSpan $span): string
    {
        $serialized = sprintf("Name: %s\n", $span->getName());
        $serialized .= sprintf("TraceID: %s\n", $span->getTraceId());
        $serialized .= sprintf("SpanID: %s\n", $span->getSpanId());
        if (!is_null($parentID = $span->getParentId())) {
            $serialized .= sprintf("StartTime: %s\n", $parentID);
        }
        $serialized .= sprintf("Timestamp: %s\n", $span->getTimestamp());
        $serialized .= sprintf("Duration: %s\n", $span->getDuration());
        $serialized .= sprintf("Kind: %s\n", $span->getKind());

        $serialized .= sprintf("LocalEndpoint: %s\n", $span->getLocalEndpoint()->getServiceName());

        if (\count($tags = $span->getTags()) > 0) {
            $serialized .= "Tags:\n";

            foreach ($tags as $key => $value) {
                $serialized .= sprintf("    %s: %s\n", $key, $value);
            }
        }

        if (\count($annotations = $span->getAnnotations()) > 0) {
            $serialized .= "Annotations:\n";

            foreach ($annotations as $annotation) {
                $serialized .= sprintf("    - timestamp: %s\n", $annotation["timestamp"]);
                $serialized .= sprintf("      value: %s\n", $annotation["value"]);
            }
        }

        if (!is_null($remoteEndpoint = $span->getRemoteEndpoint())) {
            $serialized .= sprintf("RemoteEndpoint: %s\n", $remoteEndpoint->getServiceName());
        }

        return $serialized;
    }
}
