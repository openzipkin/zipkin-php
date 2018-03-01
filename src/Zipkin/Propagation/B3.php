<?php

namespace Zipkin\Propagation;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Zipkin\Propagation\Exceptions\InvalidTraceContextArgument;
use Zipkin\Propagation\TraceContext;

/**
 * @see https://github.com/openzipkin/b3-propagation
 */
final class B3 implements Propagation
{
    /**
     * 128 or 64-bit trace ID lower-hex encoded into 32 or 16 characters (required)
     */
    const TRACE_ID_NAME = 'X-B3-TraceId';
    
    /**
     * 64-bit span ID lower-hex encoded into 16 characters (required)
     */
    const SPAN_ID_NAME = 'X-B3-SpanId';
    
    /**
     * 64-bit parent span ID lower-hex encoded into 16 characters (absent on root span)
     */
    const PARENT_SPAN_ID_NAME = 'X-B3-ParentSpanId';
    
    /**
     * '1' means report this span to the tracing system, '0' means do not. (absent means defer the
     * decision to the receiver of this header).
     */
    const SAMPLED_NAME = 'X-B3-Sampled';
    
    /**
     * '1' implies sampled and is a request to override collection-tier sampling policy.
     */
    const FLAGS_NAME = 'X-B3-Flags';

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * @return array|string[]
     */
    public function getKeys()
    {
        return [
            self::TRACE_ID_NAME,
            self::SPAN_ID_NAME,
            self::PARENT_SPAN_ID_NAME,
            self::SAMPLED_NAME,
            self::FLAGS_NAME,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getInjector(Setter $setter)
    {
        /**
         * @param TraceContext $traceContext
         * @param &$carrier
         * @return void
         */
        return function (TraceContext $traceContext, &$carrier) use ($setter) {
            $setter->put($carrier, self::TRACE_ID_NAME, $traceContext->getTraceId());
            $setter->put($carrier, self::SPAN_ID_NAME, $traceContext->getSpanId());

            if ($traceContext->getParentId() !== null) {
                $setter->put($carrier, self::PARENT_SPAN_ID_NAME, $traceContext->getParentId());
            }

            if ($traceContext->isSampled() !== null) {
                $setter->put($carrier, self::SAMPLED_NAME, $traceContext->isSampled() ? '1' : '0');
            }

            $setter->put($carrier, self::FLAGS_NAME, $traceContext->isDebug() ? '1' : '0');
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getExtractor(Getter $getter)
    {
        /**
         * @param mixed $carrier
         * @return TraceContext|SamplingFlags
         */
        return function ($carrier) use ($getter) {
            $sampledString = $getter->get($carrier, self::SAMPLED_NAME);

            $isSampled = SamplingFlags::EMPTY_SAMPLED;
            if ($sampledString === '1' || strtolower($sampledString) === 'true') {
                $isSampled = true;
            } elseif ($sampledString === '0' || strtolower($sampledString) === 'false') {
                $isSampled = false;
            }

            $isDebug = $getter->get($carrier, self::FLAGS_NAME);
            if ($isDebug === null) {
                $isDebug = SamplingFlags::EMPTY_DEBUG;
            } else {
                $isDebug = ($isDebug === '1');
            }

            $traceId = $getter->get($carrier, self::TRACE_ID_NAME);

            if ($isSampled === null && $isDebug === null && $traceId === null) {
                return DefaultSamplingFlags::createAsEmpty();
            }

            $spanId = $getter->get($carrier, self::SPAN_ID_NAME);

            if ($spanId === null) {
                return DefaultSamplingFlags::create($isSampled, $isDebug);
            }

            $parentSpanId = $getter->get($carrier, self::PARENT_SPAN_ID_NAME);

            try {
                return TraceContext::create($traceId, $spanId, $parentSpanId, $isSampled, $isDebug);
            } catch (InvalidTraceContextArgument $e) {
                $this->logger->debug(sprintf(
                    'Failed to extract propagated context: %s',
                    $e->getMessage()
                ));

                return DefaultSamplingFlags::createAsEmpty();
            }
        };
    }
}
