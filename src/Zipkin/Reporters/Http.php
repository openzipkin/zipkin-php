<?php

namespace Zipkin\Reporters;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Zipkin\Recording\Span;
use Zipkin\Reporter;
use Zipkin\Reporters\Http\ClientFactory;
use Zipkin\Reporters\Http\CurlFactory;

final class Http implements Reporter
{
    /**
     * @var CurlFactory
     */
    private $clientFactory;

    /**
     * @var array
     */
    private $options;

    public function __construct(
        ClientFactory $requesterFactory = null,
        array $options = []
    ) {
        $this->clientFactory = $requesterFactory ?: CurlFactory::create();

        $defaultOptions = [
            'endpoint_url' => 'http://localhost:9411/api/v2/spans',
        ];
        $this->options = array_merge($defaultOptions, $options);
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
        $client($payload);
    }
}
