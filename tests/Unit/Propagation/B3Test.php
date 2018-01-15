<?php

namespace ZipkinTests\Unit\Propagation;

use ArrayObject;
use PHPUnit_Framework_TestCase;
use Zipkin\Propagation\B3;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Propagation\Map;
use Zipkin\Propagation\TraceContext;

final class B3Test extends PHPUnit_Framework_TestCase
{
    const TRACE_ID_NAME = 'x-b3-Traceid';
    const SPAN_ID_NAME = 'x-b3-SpanId';
    const PARENT_SPAN_ID_NAME = 'x-b3-parentSpanId';
    const SAMPLED_NAME = 'X-B3-Sampled';
    const FLAGS_NAME = 'x-b3-flags';

    const TEST_TRACE_ID = 'bd7a977555f6b982';
    const TEST_PARENT_ID = 'bd7a977555f6b982';
    const TEST_SPAN_ID = 'be2d01e33cc78d97';
    const TEST_SAMPLE = true;
    const TEST_DEBUG = false;

    /**
     * @dataProvider carrierProvider
     */
    public function testGetInjectorReturnsTheExpectedFunction($carrier)
    {
        $context = TraceContext::create(
            self::TEST_TRACE_ID,
            self::TEST_SPAN_ID,
            self::TEST_PARENT_ID,
            self::TEST_SAMPLE,
            self::TEST_DEBUG
        );
        $setterNGetter = new Map();
        $b3Propagator = new B3();
        $injector = $b3Propagator->getInjector($setterNGetter);
        $injector($context, $carrier);

        $this->assertEquals(self::TEST_TRACE_ID, $carrier[strtolower(self::TRACE_ID_NAME)]);
        $this->assertEquals(self::TEST_SPAN_ID, $carrier[strtolower(self::SPAN_ID_NAME)]);
        $this->assertEquals(self::TEST_PARENT_ID, $carrier[strtolower(self::PARENT_SPAN_ID_NAME)]);
    }

    /**
     * @dataProvider carrierProvider
     */
    public function testExtractorExtractsTheExpectedValuesForEmptySampling($carrier)
    {
        $getter = new Map();
        $b3Propagator = new B3();
        $extractor = $b3Propagator->getExtractor($getter);
        $samplingFlags = $extractor($carrier);

        $this->assertInstanceOf(DefaultSamplingFlags::class, $samplingFlags);
        $this->assertNull($samplingFlags->isSampled());
    }

    /**
     * @dataProvider carrierProvider
     */
    public function testExtractorExtractsTheExpectedValuesForSamplingDebug($carrier)
    {
        $isSampled = $this->randomBoolean();

        $carrier[strtolower(self::SAMPLED_NAME)] = $isSampled;
        $carrier[strtolower(self::FLAGS_NAME)] = '1';

        $getter = new Map();
        $b3Propagator = new B3();
        $extractor = $b3Propagator->getExtractor($getter);
        $samplingFlags = $extractor($carrier);

        $this->assertInstanceOf(DefaultSamplingFlags::class, $samplingFlags);
        $this->assertTrue($samplingFlags->isDebug());
    }

    /**
     * @dataProvider carrierProvider
     */
    public function testExtractorExtractsTheExpectedValuesForSampling($carrier)
    {
        $isSampled = $this->randomBoolean();

        $carrier[strtolower(self::TRACE_ID_NAME)] = self::TEST_TRACE_ID;
        $carrier[strtolower(self::SAMPLED_NAME)] = $isSampled ? '1' : '0';
        $carrier[strtolower(self::FLAGS_NAME)] = '0';

        $getter = new Map();
        $b3Propagator = new B3();
        $extractor = $b3Propagator->getExtractor($getter);
        $samplingFlags = $extractor($carrier);

        $this->assertInstanceOf(DefaultSamplingFlags::class, $samplingFlags);
        $this->assertEquals($isSampled, $samplingFlags->isSampled());
    }

    /**
     * @dataProvider carrierProvider
     */
    public function testExtractorExtractsTheExpectedValuesForTraceContext($carrier)
    {
        $carrier[strtolower(self::TRACE_ID_NAME)] = self::TEST_TRACE_ID;
        $carrier[strtolower(self::SPAN_ID_NAME)] = self::TEST_SPAN_ID;
        $carrier[strtolower(self::PARENT_SPAN_ID_NAME)] = self::TEST_PARENT_ID;

        $getter = new Map();
        $b3Propagator = new B3();
        $extractor = $b3Propagator->getExtractor($getter);
        $context = $extractor($carrier);

        $this->assertInstanceOf(TraceContext::class, $context);
        $this->assertEquals(self::TEST_TRACE_ID, $context->getTraceId());
        $this->assertEquals(self::TEST_SPAN_ID, $context->getSpanId());
        $this->assertEquals(self::TEST_PARENT_ID, $context->getParentId());
    }

    public function carrierProvider()
    {
        return [
            [new ArrayObject()],
            [[]]
        ];
    }

    private function randomBoolean()
    {
        return (mt_rand(0, 1) === 1);
    }
}
