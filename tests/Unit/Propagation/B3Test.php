<?php

namespace ZipkinTests\Unit\Propagation;

use Zipkin\Propagation\TraceContext;
use Zipkin\Propagation\RemoteSetter;
use Zipkin\Propagation\Map;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Propagation\B3;
use Psr\Log\NullLogger;
use Psr\Log\LoggerTrait;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

final class B3Test extends TestCase
{
    private const TRACE_ID_NAME = 'x-b3-traceid';
    private const SPAN_ID_NAME = 'x-b3-spanid';
    private const PARENT_SPAN_ID_NAME = 'x-b3-parentspanid';
    private const SAMPLED_NAME = 'x-b3-sampled';
    private const FLAGS_NAME = 'x-b3-flags';
    private const SINGLE_VALUE_NAME = 'b3';

    private const TEST_TRACE_ID = 'bd7a977555f6b982';
    private const TEST_PARENT_ID = 'bd7a977555f6b982';
    private const TEST_SPAN_ID = 'be2d01e33cc78d97';
    private const TEST_SAMPLE = true;
    private const TEST_DEBUG = false;
    private const TEST_SINGLE_HEADER = 'bd7a977555f6b982-be2d01e33cc78d97-1-bd7a977555f6b982';
    private const TEST_SINGLE_HEADER_NO_PARENT = 'bd7a977555f6b982-be2d01e33cc78d97-1';

    public function testKeysIncludesAllByDefault()
    {
        $b3Propagator = new B3();
        $this->assertEquals([
            'b3',
            'X-B3-TraceId',
            'X-B3-SpanId',
            'X-B3-ParentSpanId',
            'X-B3-Sampled',
            'X-B3-Flags',
        ], $b3Propagator->getKeys());
    }

    public function testKeysIncludeMultiOnly()
    {
        $b3Propagator = new B3(new NullLogger(), [
            'PRODUCER' => ['multi'],
            'CLIENT' => ['multi'],
            'default' => ['multi'],
        ]);

        $this->assertEquals([
            'X-B3-TraceId',
            'X-B3-SpanId',
            'X-B3-ParentSpanId',
            'X-B3-Sampled',
            'X-B3-Flags',
        ], $b3Propagator->getKeys());
    }

    public function testKeysIncludeSingleOnly()
    {
        $b3Propagator = new B3(new NullLogger(), [
            'PRODUCER' => ['single'],
            'CLIENT' => ['single'],
            'default' => ['single'],
        ]);

        $this->assertEquals(['b3'], $b3Propagator->getKeys());
    }

    public function testKeysIncludeSingleAndMulti()
    {
        $b3Propagator = new B3(new NullLogger(), [
            'PRODUCER' => ['single'],
            'CLIENT' => ['multi'],
            'default' => ['single'],
        ]);

        $this->assertEquals([
            'b3',
            'X-B3-TraceId',
            'X-B3-SpanId',
            'X-B3-ParentSpanId',
            'X-B3-Sampled',
            'X-B3-Flags',
        ], $b3Propagator->getKeys());
    }

    public function injectorProvider(): array
    {
        return [
            'multi' => [
                ['default' => [B3::INJECT_MULTI]],
                [
                    self::TEST_TRACE_ID => self::TRACE_ID_NAME,
                    self::TEST_SPAN_ID => self::SPAN_ID_NAME,
                    self::TEST_PARENT_ID =>  self::PARENT_SPAN_ID_NAME,
                ]
            ],
            'single' => [
                ['default' => [B3::INJECT_SINGLE]],
                [
                    self::TEST_SINGLE_HEADER => self::SINGLE_VALUE_NAME,
                ]
            ],
            'both single and multi' => [
                ['default' => [B3::INJECT_MULTI, B3::INJECT_SINGLE]],
                [
                    self::TEST_TRACE_ID => self::TRACE_ID_NAME,
                    self::TEST_SPAN_ID => self::SPAN_ID_NAME,
                    self::TEST_PARENT_ID =>  self::PARENT_SPAN_ID_NAME,
                    self::TEST_SINGLE_HEADER => self::SINGLE_VALUE_NAME,
                ]
            ],
            'single no parent' => [
                ['default' => [B3::INJECT_SINGLE_NO_PARENT]],
                [
                    self::TEST_SINGLE_HEADER_NO_PARENT => self::SINGLE_VALUE_NAME,
                ]
            ],
        ];
    }

    /**
     * @dataProvider injectorProvider
     */
    public function testGetInjectorReturnsTheExpectedFunction(array $injectorsFn, array $headerChecks)
    {
        $carrier = [];
        $context = TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_SPAN_ID,
            self::TEST_PARENT_ID,
            self::TEST_SAMPLE,
            self::TEST_DEBUG
        );
        $setterNGetter = new Map();
        $b3Propagator = new B3(new NullLogger(), $injectorsFn);
        $injector = $b3Propagator->getInjector($setterNGetter);
        $injector($context, $carrier);

        foreach ($headerChecks as $key => $value) {
            $this->assertEquals($key, $setterNGetter->get($carrier, $value));
        }
    }

    public function testGetInjectorInjectsDebugAndNotSampling()
    {
        $carrier = [];
        $context = TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_SPAN_ID,
            self::TEST_PARENT_ID,
            true,
            true
        );
        $setterNGetter = new Map();
        $b3Propagator = new B3(new NullLogger());
        $injector = $b3Propagator->getInjector($setterNGetter);
        $injector($context, $carrier);

        $this->assertArrayHasKey('x-b3-flags', $carrier);
        $this->assertArrayNotHasKey('x-b3-sampled', $carrier);
    }

    public function kindRemoteSetterProvider(): array
    {
        return [
            'client' => [
                new class() extends Map implements RemoteSetter {
                    public function getKind(): string
                    {
                        return 'CLIENT';
                    }
                },
                [
                    self::TEST_TRACE_ID => self::TRACE_ID_NAME,
                    self::TEST_SPAN_ID => self::SPAN_ID_NAME,
                    self::TEST_PARENT_ID =>  self::PARENT_SPAN_ID_NAME,
                ]
            ],
            'producer' => [
                new class() extends Map implements RemoteSetter {
                    public function getKind(): string
                    {
                        return 'PRODUCER';
                    }
                },
                [
                    self::TEST_SINGLE_HEADER_NO_PARENT => self::SINGLE_VALUE_NAME,
                ]
            ],
            'default' => [
                new Map(),
                [
                    self::TEST_TRACE_ID => self::TRACE_ID_NAME,
                    self::TEST_SPAN_ID => self::SPAN_ID_NAME,
                    self::TEST_PARENT_ID =>  self::PARENT_SPAN_ID_NAME,
                ]
            ],
        ];
    }

    /**
     * @dataProvider kindRemoteSetterProvider
     */
    public function testGetInjectorReturnsTheExpectedFunctionPerKind($remoteSetter, array $headerChecks)
    {
        $carrier = [];
        $context = TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_SPAN_ID,
            self::TEST_PARENT_ID,
            self::TEST_SAMPLE,
            self::TEST_DEBUG
        );
        $b3Propagator = new B3(new NullLogger());
        $injector = $b3Propagator->getInjector($remoteSetter);
        $injector($context, $carrier);

        foreach ($headerChecks as $key => $value) {
            $this->assertEquals($key, $carrier[$value]);
        }
    }

    public function testExtractorExtractsTheExpectedValuesForEmptySampling()
    {
        $carrier = [];
        $getter = new Map();
        $b3Propagator = new B3();
        $extractor = $b3Propagator->getExtractor($getter);
        $samplingFlags = $extractor($carrier);

        $this->assertInstanceOf(DefaultSamplingFlags::class, $samplingFlags);
        $this->assertNull($samplingFlags->isSampled());
    }

    public function samplingDebugCarrierProvider(): array
    {
        return [
            'multi' => [
                [strtolower(self::FLAGS_NAME) => '1'],
            ],
            'single' => [
                [strtolower(self::SINGLE_VALUE_NAME) => 'd'],
            ],
        ];
    }

    /**
     * @dataProvider samplingDebugCarrierProvider
     */
    public function testExtractorExtractsTheExpectedValuesForSamplingDebug(array $carrier)
    {
        $getter = new Map();
        $b3Propagator = new B3();
        $extractor = $b3Propagator->getExtractor($getter);
        $samplingFlags = $extractor($carrier);

        $this->assertInstanceOf(DefaultSamplingFlags::class, $samplingFlags);
        $this->assertTrue($samplingFlags->isSampled());
        $this->assertTrue($samplingFlags->isDebug());
    }

    public function samplingCarrierProvider(): array
    {
        return [
            'multi sampled' => [
                [
                    strtolower(self::TRACE_ID_NAME) => self::TEST_TRACE_ID,
                    strtolower(self::SAMPLED_NAME) => '1',
                ],
                true
            ],
            'multi not sampled' => [
                [
                    strtolower(self::SAMPLED_NAME) => '0',
                ],
                false
            ],
            'single sampled' => [
                [strtolower(self::SINGLE_VALUE_NAME) => '1'],
                true
            ],
            'single not sampled' => [
                [strtolower(self::SINGLE_VALUE_NAME) => '0'],
                false
            ],
        ];
    }

    /**
     * @dataProvider samplingCarrierProvider
     */
    public function testExtractorExtractsTheExpectedValuesForSampling(array $carrier, bool $isSampled)
    {
        $getter = new Map();
        $b3Propagator = new B3();
        $extractor = $b3Propagator->getExtractor($getter);
        $samplingFlags = $extractor($carrier);

        $this->assertInstanceOf(DefaultSamplingFlags::class, $samplingFlags);
        $this->assertEquals($isSampled, $samplingFlags->isSampled());
    }

    public function traceContextCarrierProvider(): array
    {
        return [
            'multi' => [
                [
                    strtolower(self::TRACE_ID_NAME) => self::TEST_TRACE_ID,
                    strtolower(self::SPAN_ID_NAME) => self::TEST_SPAN_ID,
                    strtolower(self::PARENT_SPAN_ID_NAME) => self::TEST_PARENT_ID,
                ]
            ],
            'single' => [
                [
                    strtolower(self::SINGLE_VALUE_NAME) => self::TEST_SINGLE_HEADER,
                ]
            ],
        ];
    }

    /**
     * @dataProvider traceContextCarrierProvider
     */
    public function testExtractorExtractsTheExpectedValuesForTraceContext(array $carrier)
    {
        $getter = new Map();
        $b3Propagator = new B3();
        $extractor = $b3Propagator->getExtractor($getter);
        $context = $extractor($carrier);
        $this->assertInstanceOf(TraceContext::class, $context);
        $this->assertEquals(self::TEST_TRACE_ID, $context->getTraceId());
        $this->assertEquals(self::TEST_SPAN_ID, $context->getSpanId());
        $this->assertEquals(self::TEST_PARENT_ID, $context->getParentId());
    }

    public function testInvalidPropagationValuesFail()
    {
        $carrier = [];
        $carrier[strtolower(self::TRACE_ID_NAME)] = 'xyz';
        $carrier[strtolower(self::SPAN_ID_NAME)] = 'mno';
        $test = $this;

        $logger = new class($test) implements LoggerInterface {
            use LoggerTrait;

            private $test;

            public function __construct(TestCase $test)
            {
                $this->test = $test;
            }

            public function log($level, $message, array $context = array())
            {
                $this->test->assertEquals('debug', $level);
            }
        };
        $getter = new Map();
        $b3Propagator = new B3($logger);
        $extractor = $b3Propagator->getExtractor($getter);
        $this->assertNull($extractor($carrier)->isSampled());
    }
}
