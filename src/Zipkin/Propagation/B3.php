<?php

declare(strict_types=1);

namespace Zipkin\Propagation;

use InvalidArgumentException;
use Zipkin\Kind;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Zipkin\Propagation\RemoteSetter;
use Zipkin\Propagation\TraceContext;
use Zipkin\Propagation\Exceptions\InvalidTraceContextArgument;

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

    /**
     * @see https://github.com/openzipkin/b3-propagation#single-header
     */
    private const SINGLE_VALUE_NAME = 'b3';

    private const MULTI_VALUE_NAMES = [
        self::TRACE_ID_NAME,
        self::SPAN_ID_NAME,
        self::PARENT_SPAN_ID_NAME,
        self::SAMPLED_NAME,
        self::FLAGS_NAME,
    ];

    private const INJECTORS = [
        self::INJECT_SINGLE => [self::class, 'injectSingleValue'],
        self::INJECT_SINGLE_NO_PARENT => [self::class, 'injectSingleValueNoParent'],
        self::INJECT_MULTI => [self::class, 'injectMultiValues'],
    ];

    private const KEYS = [
        self::INJECT_SINGLE => [self::SINGLE_VALUE_NAME],
        self::INJECT_SINGLE_NO_PARENT => [self::SINGLE_VALUE_NAME],
        self::INJECT_MULTI => self::MULTI_VALUE_NAMES,
    ];

    /**
     * Inject the single value context
     */
    public const INJECT_SINGLE = 'single';
    
    /**
     * Inject the single value context excluding the parent (e.g. for messaging)
     */
    public const INJECT_SINGLE_NO_PARENT = 'single_no_parent';

    /**
     * Inject the multi value context
     */
    public const INJECT_MULTI = 'multi';

    /**
     * @var string[]|array
     */
    private $keys = [];

    private const NO_KIND = 'no_kind';

    /**
     * @var array[]
     */
    private $injectorsFn = [
        Kind\CLIENT => [self::INJECT_MULTI, self::INJECT_SINGLE],
        Kind\PRODUCER => [self::INJECT_SINGLE_NO_PARENT],
        self::NO_KIND => [self::INJECT_MULTI],
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        LoggerInterface $logger = null,
        array $kindInjectors = []
    ) {
        $this->logger = $logger ?: new NullLogger();

        foreach ($kindInjectors as $kind => $injectors) {
            if ($kind !== Kind\CLIENT || $kind !== Kind\PRODUCER || $kind !== self::NO_KIND) {
                throw new InvalidArgumentException(sprintf(
                    'Unkown kind "%s" for injector, "%s", "%s" or "%s" supported.',
                    $kind,
                    Kind\CLIENT,
                    Kind\PRODUCER,
                    self::NO_KIND
                ));
            }

            $this->injectorsFn[$kind] = array_map(function ($injectorName) {
                return self::INJECTORS[$injectorName];
            }, $injectors);

            $this->keys = array_merge($this->keys, self::keysForInjectors($injectors));
        }
    }

    private static function keysForInjectors(array $injectors): array
    {
        $keys = [];
        foreach ($injectors as $injectorName) {
            $keys = array_merge($keys, self::KEYS[$injectorName]);
        }
        return $keys;
    }

    /**
     * @return array|string[]
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    /**
     * {@inheritdoc}
     */
    public function getInjector(Setter $setter): callable
    {
        $injectorKind = ($setter instanceof RemoteSetter) ? $setter->getKind() : self::NO_KIND;

        /**
         * @param TraceContext $traceContext
         * @param &$carrier
         * @return void
         */
        return function (SamplingFlags $traceContext, &$carrier) use ($setter, $injectorKind) {
            if ($traceContext->isEmpty()) {
                return;
            }

            foreach ($this->injectorsFn[$injectorKind] as $injectorFn) {
                ($injectorFn)($setter, $traceContext, $carrier);
            }
        };
    }

    private static function injectSingleValue(Setter $setter, TraceContext $traceContext, &$carrier)
    {
        $setter->put($carrier, self::SINGLE_VALUE_NAME, self::buildSingleValue($traceContext));
    }

    private static function injectSingleValueNoParent(Setter $setter, TraceContext $traceContext, &$carrier)
    {
        $setter->put($carrier, self::SINGLE_VALUE_NAME, self::buildSingleValue($traceContext, false));
    }

    private static function injectMultiValues(Setter $setter, TraceContext $traceContext, &$carrier)
    {
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

    private static function buildSingleValue(SamplingFlags $traceContext, bool $includeParent = true): string
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

                if ($traceContext->getParentId() !== null && $includeParent) {
                    $value .= '-' . $traceContext->getParentId();
                }
            }

            return $value;
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
        if ($value === '') {
            return null;
        }

        $pieces = explode('-', $value);
        $numberOfPieces = count($pieces);
        if ($numberOfPieces === 1) { // {sampling}
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

        if ($numberOfPieces >= 2) { // {trace_id}-{span_id}[-{sampling}-{parent_id}]
            $traceId = $pieces[0];
            $spanId = $pieces[1];
            $isSampled = DefaultSamplingFlags::EMPTY_SAMPLED;
            $isDebug = DefaultSamplingFlags::EMPTY_DEBUG;
            if ($numberOfPieces > 2) { // {trace_id}-{span_id}-{sampling}[-{parent_id}]
                $samplingBit = $pieces[2];
                if ($samplingBit === '0') {
                    $isSampled = false;
                } elseif ($samplingBit === '1') {
                    $isSampled = true;
                } elseif ($samplingBit === 'd') {
                    $isDebug = true;
                } else {
                    throw InvalidTraceContextArgument::forSampling($samplingBit);
                }
            }

            $parentId = $numberOfPieces > 3 ? $pieces[3] : null; // {trace_id}-{span_id}-{sampling}-{parent_id}
            return TraceContext::create(
                $traceId,
                $spanId,
                $parentId,
                $isSampled,
                $isDebug,
                \strlen($pieces[0]) == 32
            );
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
         * @var ?bool $isDebug
         */
        $isDebug = SamplingFlags::EMPTY_DEBUG;
        if ($isDebugRaw !== null) {
            $isDebug = ($isDebugRaw === '1');
        }

        $traceId = $getter->get($carrier, self::TRACE_ID_NAME);

        if ($traceId === null) {
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
