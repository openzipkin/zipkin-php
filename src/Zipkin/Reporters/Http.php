<?php

namespace Zipkin\Reporters;

use RuntimeException;
use Zipkin\Recording\Span;
use Zipkin\Reporter;
use Zipkin\Reporters\Http\ClientFactory;
use Zipkin\Reporters\Http\CurlFactory;

final class Http implements Reporter
{
    const DEFAULT_OPTIONS = [
        'endpoint_url' => 'http://localhost:9411/api/v2/spans',
    ];

    /**
     * @var CurlFactory
     */
    private $clientFactory;

    /**
     * @var array
     */
    private $options;

    /**
     * @var Metrics
     */
    private $reportMetrics;

    public function __construct(
        ClientFactory $requesterFactory = null,
        array $options = [],
        Metrics $reporterMetrics = null
    ) {
        $this->clientFactory = $requesterFactory ?: CurlFactory::create();
        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);
        $this->reportMetrics = $reporterMetrics;
    }

    /**
     * @param Span[] $spans
     * @return void
     */
    public function report(array $spans)
    {
        $payload = json_encode(array_map(function (Span $span) {
            return $span->toArray();
        }, $spans));

        $client = $this->clientFactory->build($this->options);

        try {
            $client($payload);
            $this->reportMetrics->incrementSpans(count($spans));
        } catch (RuntimeException $e) {
            $this->reportMetrics->incrementSpansDropped(count($spans));
        }
    }
}
