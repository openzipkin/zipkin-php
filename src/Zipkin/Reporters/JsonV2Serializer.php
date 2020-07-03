<?php

declare(strict_types=1);

namespace Zipkin\Reporters;

use Zipkin\Recording\ReadbackSpan;
use Zipkin\ErrorParser;
use Zipkin\DefaultErrorParser;

class JsonV2Serializer implements SpanSerializer
{
    /**
     * @var ErrorParser
     */
    private $errorParser;

    public function __construct(ErrorParser $errorParser = null)
    {
        $this->errorParser = $errorParser ?? new DefaultErrorParser();
    }

    /**
     * @param ReadbackSpan[]|array $spans
     */
    public function serialize(array $spans):string
    {
        $spansAsArray = array_map(function (ReadbackSpan $span) {
            $spanAsArray = [
                'id' => $span->getSpanId(),
                'name' => $span->getName(),
                'traceId' => $span->getTraceId(),
                'timestamp' => $span->getTimestamp(),
                'duration' => $span->getDuration(),
                'localEndpoint' => $span->getLocalEndpoint()->toArray(),
            ];
    
            if ($span->getParentId() !== null) {
                $spanAsArray['parentId'] = $span->getParentId();
            }
    
            if ($span->isDebug() === true) {
                $spanAsArray['debug'] = true;
            }
    
            if ($span->isShared() === true) {
                $spanAsArray['shared'] = true;
            }
    
            if ($span->getKind() !== null) {
                $spanAsArray['kind'] = $span->kind;
            }
    
            if ($span->getRemoteEndpoint() !== null) {
                $spanAsArray['remoteEndpoint'] = $span->getRemoteEndpoint()->toArray();
            }
    
            if (!empty($span->getAnnotations())) {
                $spanAsArray['annotations'] = $span->getAnnotations() + $this->errorParser->parseAnnotations($span->getError());
            }
    
            if (!empty($span->getTags())) {
                $spanAsArray['tags'] = $span->getTags() + $this->errorParser->parseTags($span->getError());
            }
    
            return \json_encode($spanAsArray);
        }, $spans);

        return '[' . implode(',', $spansAsArray) . ']';
    }
}
