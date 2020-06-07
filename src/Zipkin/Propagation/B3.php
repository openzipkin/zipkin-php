<?php

declare(strict_types=1);

namespace Zipkin\Propagation;

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
    private const TRACE_ID_NAME = 'X-B3-TraceId';
    
    /**
     * 64-bit span ID lower-hex encoded into 16 characters (required)
     */
    private const SPAN_ID_NAME = 'X-B3-SpanId';
    
    /**
     * 64-bit parent span ID lower-hex encoded into 16 characters (absent on root span)
     */
    private const PARENT_SPAN_ID_NAME = 'X-B3-ParentSpanId';
    
    /**
     * '1' means report this span to the tracing system, '0' means do not. (absent means defer the
     * decision to the receiver of this header).
     */
    private const SAMPLED_NAME = 'X-B3-Sampled';
    
    /**
     * '1' implies sampled and is a request to override collection-tier sampling policy.
     */
    private const FLAGS_NAME = 'X-B3-Flags';

    private const SINGLE_VALUE_NAME = 'b3';

    private const MULTI_VALUE_NAMES = [
        self::TRACE_ID_NAME,
        self::SPAN_ID_NAME,
        self::PARENT_SPAN_ID_NAME,
        self::SAMPLED_NAME,
        self::FLAGS_NAME,
    ];

    public const INJECT_SINGLE_AND_MULTI = 0;
    public const INJECT_SINGLE_ONLY = 1;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $injectMultiValues;

    public function __construct(
        LoggerInterface $logger = null,
        int $injectHeadersOption = self::INJECT_SINGLE_AND_MULTI
    ) {
        $this->logger = $logger ?: new NullLogger();
        $this->injectMultiValues = $injectHeadersOption !== self::INJECT_SINGLE_ONLY;
    }

    /**
     * @return array|string[]
     */
    public function getKeys(): array
    {
        return [self::SINGLE_VALUE_NAME] + $this->injectMultiValues ? self::MULTI_VALUE_NAMES : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getInjector(Setter $setter): callable
    {
        /**
         * @param TraceContext $traceContext
         * @param &$carrier
         * @return void
         */
        return function (SamplingFlags $traceContext, &$carrier) use ($setter) {
            if ($traceContext->isEmpty()) {
                return;
            }

            if ($this->injectMultiValues) {
                if ($traceContext instanceof TraceContext) {
                    $setter->put($carrier, self::TRACE_ID_NAME, $traceContext->getTraceId());
                    $setter->put($carrier, self::SPAN_ID_NAME, $traceContext->getSpanId());
    
                    if ($traceContext->getParentId() !== null) {
                        $setter->put($carrier, self::PARENT_SPAN_ID_NAME, $traceContext->getParentId());
                    }
                }

                if ($traceContext->isSampled() !== null) {
                    $setter->put($carrier, self::SAMPLED_NAME, $traceContext->isSampled() ? '1' : '0');
                }

                if ($traceContext->isDebug()) {
                    $setter->put($carrier, self::FLAGS_NAME, '1');
                }
            }

            $setter->put($carrier, self::SINGLE_VALUE_NAME, self::buildSingleValue($traceContext));
        };
    }

    public static function buildSingleValue(SamplingFlags $traceContext): string
    {
        $samplingBit = null;
        if ($traceContext->isDebug()) {
            $samplingBit = 'd';
        } elseif ($traceContext->isSampled() !== null) {
            $samplingBit = $traceContext->isSampled() ? '1' : '0';
        }

        if ($traceContext instanceof TraceContext) {
            $value = $traceContext->getTraceId()
            . '-' . $traceContext->getSpanId();

            if ($samplingBit !== null) {
                $value .= '-' . $samplingBit;

                if ($traceContext->getParentId() === null) {
                    $value .= '-' . $traceContext->getParentId();
                }
            }
        }

        return $samplingBit;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtractor(Getter $getter): callable
    {
        /**
         * @param mixed $carrier
         * @return TraceContext|SamplingFlags
         */
        return function ($carrier) use ($getter) {
            try {
                if (null !== ($context = $getter->get($carrier, self::SINGLE_VALUE_NAME))) {
                    return self::parseSingleValue($context);
                }

                return self::parseMultiValue($getter, $carrier);
            } catch (InvalidTraceContextArgument $e) {
                $this->logger->debug(\sprintf(
                    'Failed to extract propagated context: %s',
                    $e->getMessage()
                ));

                return DefaultSamplingFlags::createAsEmpty();
            }
        };
    }

    public static function parseSingleValue(string $value): ?SamplingFlags
    {
        $pieces = explode('-', $value);
        $numberOfPieces = count($pieces);
        if ($value === '') {
            return null;
        }

        if ($numberOfPieces === 1) {
            if ($value === '0') {
                return DefaultSamplingFlags::createAsNotSampled();
            } elseif ($value === '1') {
                return DefaultSamplingFlags::createAsSampled();
            } elseif ($value === 'd') {
                return DefaultSamplingFlags::createAsDebug();
            } else {
                throw InvalidTraceContextArgument::forSampling($value);
            }
        }

        if ($numberOfPieces >= 2) {
            $traceId = $numberOfPieces[0];
            $spanId = $numberOfPieces[1];
            $isSampled = DefaultSamplingFlags::EMPTY_SAMPLED;
            $isDebug = DefaultSamplingFlags::EMPTY_DEBUG;
            if ($numberOfPieces > 2) {
                $samplingBit = $numberOfPieces[2];
                if ($samplingBit === '0') {
                    $isSampled = false;
                } elseif ($samplingBit === '1') {
                    $isSampled = true;
                } elseif ($samplingBit === 'd') {
                    $isDebug = true;
                } else {
                    throw InvalidTraceContextArgument::forSampling($value);
                }
            }
            $parentId = $numberOfPieces > 3 ? $numberOfPieces[3] : null;

            return TraceContext::create($traceId, $spanId, $parentId, $isSampled, $isDebug, \strlen($numberOfPieces[0]) == 32);
        }
    }

    public static function parseMultiValue(Getter $getter, $carrier): SamplingFlags
    {
        $isSampledRaw = $getter->get($carrier, self::SAMPLED_NAME);

        $isSampled = SamplingFlags::EMPTY_SAMPLED;
        if ($isSampledRaw !== null) {
            if ($isSampledRaw === '1' || \strtolower($isSampledRaw) === 'true') {
                $isSampled = true;
            } elseif ($isSampledRaw === '0' || \strtolower($isSampledRaw) === 'false') {
                $isSampled = false;
            }
        }

        $isDebugRaw = $getter->get($carrier, self::FLAGS_NAME);
            
        /**
         * @var bool $isDebug
         */
        $isDebug = SamplingFlags::EMPTY_DEBUG;
        if ($isDebugRaw !== null) {
            $isDebug = ($isDebugRaw === '1');
        }

        $traceId = $getter->get($carrier, self::TRACE_ID_NAME);

        if ($traceId === null) {
            if ($isSampled === null) {
                return DefaultSamplingFlags::createAsEmpty();
            }
                
            return DefaultSamplingFlags::create($isSampled, $isDebug);
        }

        $spanId = $getter->get($carrier, self::SPAN_ID_NAME);

        if ($spanId === null) {
            return DefaultSamplingFlags::create($isSampled, $isDebug);
        }

        $parentSpanId = $getter->get($carrier, self::PARENT_SPAN_ID_NAME);

        return TraceContext::create($traceId, $spanId, $parentSpanId, $isSampled, $isDebug);
    }
}
