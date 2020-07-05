<?php

namespace ZipkinTests\Unit;

use Zipkin\Tracer;
use Zipkin\SpanCustomizer;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Sampler;
use Zipkin\Reporters\InMemory;
use Zipkin\Reporter;
use Zipkin\RealSpan;
use Zipkin\Propagation\TraceContext;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Propagation\CurrentTraceContext;
use Zipkin\NoopSpan;
use Zipkin\Endpoint;
use ZipkinTests\Unit\InSpan\Sumer;
use Throwable;
use Prophecy\Prophecy\ObjectProphecy;
use PHPUnit\Framework\TestCase;
use OutOfBoundsException;

final class TracerTest extends TestCase
{
    /**
     * @var ObjectProphecy|Reporter
     */
    private $reporter;

    /**
     * @var ObjectProphecy|Sampler
     */
    private $sampler;

    /**
     * @var CurrentTraceContext
     */
    private $currentTracerContext;

    public function setUp()
    {
        $this->reporter = $this->prophesize(Reporter::class);
        $this->sampler = $this->prophesize(Sampler::class);
        $this->currentTracerContext = new CurrentTraceContext;
    }

    public function testNewTraceSuccess()
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            $this->sampler->reveal(),
            false, // usesTraceId128bits
            $this->currentTracerContext,
            false // isNoop
        );

        $samplingFlags = DefaultSamplingFlags::create(true, false);

        $span = $tracer->newTrace($samplingFlags);

        $this->assertEquals(true, $span->getContext()->isSampled());
        $this->assertEquals(false, $span->getContext()->isDebug());
        $this->assertNull($span->getContext()->getParentId());
        $this->assertFalse($span->getContext()->usesTraceId128bits());
        $this->assertEquals($span->getContext()->getTraceId(), $span->getContext()->getSpanId());
    }

    public function testNewTraceSuccessWith128bits()
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            $this->sampler->reveal(),
            true, // usesTraceId128bits
            new CurrentTraceContext,
            false // isNoop
        );

        $samplingFlags = DefaultSamplingFlags::create(true, false);

        $span = $tracer->newTrace($samplingFlags);

        $this->assertEquals(true, $span->getContext()->isSampled());
        $this->assertEquals(false, $span->getContext()->isDebug());
        $this->assertNull($span->getContext()->getParentId());
        $this->assertTrue($span->getContext()->usesTraceId128bits());
    }

    public function testNewChildIsBeingCreatedAsNoop()
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            $this->sampler->reveal(),
            false,
            $this->currentTracerContext,
            false
        );

        $samplingFlags = DefaultSamplingFlags::create(false, false);

        $traceContext = TraceContext::createAsRoot($samplingFlags);

        $span = $tracer->newChild($traceContext);

        $this->assertInstanceOf(NoopSpan::class, $span);
    }

    public function testNewChildIsBeingCreatedAsSampled()
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            $this->sampler->reveal(),
            false,
            $this->currentTracerContext,
            false
        );

        $samplingFlags = DefaultSamplingFlags::create(true, false);

        $traceContext = TraceContext::createAsRoot($samplingFlags);

        $span = $tracer->newChild($traceContext);

        $this->assertInstanceOf(RealSpan::class, $span);
    }

    public function testNewTraceIsSampledOnAlwaysSampling()
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            BinarySampler::createAsAlwaysSample(),
            false,
            $this->currentTracerContext,
            false
        );

        $span = $tracer->newTrace(DefaultSamplingFlags::createAsEmpty());
        $this->assertTrue($span->getContext()->isSampled());
    }

    public function testNewTraceIsSampledOnNeverSampling()
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            BinarySampler::createAsNeverSample(),
            false,
            $this->currentTracerContext,
            false
        );

        $span = $tracer->newTrace(DefaultSamplingFlags::createAsEmpty());
        $this->assertFalse($span->getContext()->isSampled());
    }

    public function testNextSpanIsCreatedFromCurrentTraceContext()
    {
        $context = TraceContext::createAsRoot();

        $this->currentTracerContext->createScopeAndRetrieveItsCloser($context);

        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            BinarySampler::createAsNeverSample(),
            false,
            $this->currentTracerContext,
            false
        );

        $span = $tracer->nextSpan();

        $this->assertContextParentOf($context, $span->getContext());
    }

    public function testNextSpanIsCreatedFromContext()
    {
        $context = TraceContext::createAsRoot();

        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            BinarySampler::createAsNeverSample(),
            false,
            $this->currentTracerContext,
            false
        );

        $span = $tracer->nextSpan($context);
        $this->assertContextParentOf($context, $span->getContext());
    }

    /**
     * @dataProvider samplingFlagsDataProvider
     */
    public function testNextSpanIsCreatedFromSamplingFlags($sampled, $debug)
    {
        $samplingFlags = DefaultSamplingFlags::create($sampled, $debug);

        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            BinarySampler::createAsNeverSample(),
            false,
            $this->currentTracerContext,
            false
        );

        $span = $tracer->nextSpan($samplingFlags);

        $this->assertSameSamplingFlags($samplingFlags, $span->getContext());
        $this->assertNotNull($span->getContext()->isSampled());
    }

    /**
     * @dataProvider emptySamplingFlagsDataProvider
     */
    public function testNextSpanIsCreatedFromEmptySamplingFlags($sampler, $isNoop)
    {
        $samplingFlags = DefaultSamplingFlags::createAsEmpty();

        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            $sampler,
            false,
            $this->currentTracerContext,
            false
        );

        $span = $tracer->nextSpan($samplingFlags);
        $this->assertEquals($isNoop, $span->isNoop());
        $this->assertNotNull($span->getContext()->isSampled());
    }

    public function emptySamplingFlagsDataProvider()
    {
        return [
            [BinarySampler::createAsNeverSample(), true],
            [BinarySampler::createAsAlwaysSample(), false],
        ];
    }

    public function samplingFlagsDataProvider()
    {
        return [
            // [$isSampled, $isDebug]
            [null, true],
            [null, false],
            [true, true],
            [true, false],
            [false, true],
            [false, false],
        ];
    }

    private function assertSameSamplingFlags(SamplingFlags $samplingFlags1, SamplingFlags $samplingFlags2)
    {
        $this->assertEquals($samplingFlags1->isSampled(), $samplingFlags2->isSampled());
        $this->assertEquals($samplingFlags1->isDebug(), $samplingFlags2->isDebug());
    }

    private function assertContextParentOf(TraceContext $parentContext, TraceContext $childContext)
    {
        $this->assertEquals($parentContext->getTraceId(), $childContext->getTraceId());
        $this->assertEquals($parentContext->getSpanId(), $childContext->getParentId());
        $this->assertEquals($parentContext->isDebug(), $childContext->isDebug());
        $this->assertEquals($parentContext->isSampled(), $childContext->isSampled());
    }

    public function testSetSpanInScope()
    {
        $context = TraceContext::createAsRoot();
        $this->currentTracerContext->createScopeAndRetrieveItsCloser($context);

        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            BinarySampler::createAsNeverSample(),
            false,
            $this->currentTracerContext,
            false
        );

        $currentSpan = $tracer->getCurrentSpan();

        $this->assertEquals($context, $currentSpan->getContext());
    }

    /**
     * @dataProvider spanForScopeProvider
     */
    public function testOpenScopeReturnsScopeCloser($spanForScope)
    {
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $this->reporter->reveal(),
            BinarySampler::createAsNeverSample(),
            false,
            new CurrentTraceContext,
            false
        );

        $scopeCloser = $tracer->openScope($spanForScope);

        $this->assertTrue(is_callable($scopeCloser));
    }

    public function spanForScopeProvider()
    {
        return [
            [null],
            [new NoopSpan(TraceContext::createAsRoot())]
        ];
    }

    /**
     * @dataProvider sumCallables
     */
    public function testInSpanForSuccessfullCall($sumCallable)
    {
        list($tracer, $flusher) = self::createDefaultTestTracer();

        $result = $tracer->inSpan(
            $sumCallable,
            [1, 2],
            'sum',
            function (SpanCustomizer $span, ?array $args = []) {
                $span->tag('arg0', (string) $args[0]);
                $span->tag('arg1', (string) $args[1]);
            },
            function (SpanCustomizer $span, $output = null, ?Throwable $e = null) {
                $span->tag('result', (string) $output);
            }
        );

        $this->assertEquals(3, $result);
        $spans = $flusher();
        $this->assertCount(1, $spans);

        $span = $spans[0]->toArray();
        $this->assertEquals('sum', $span['name']);
        $this->assertEquals('1', $span['tags']['arg0']);
        $this->assertEquals('2', $span['tags']['arg1']);
        $this->assertEquals('3', $span['tags']['result']);
    }

    /**
     * @dataProvider sumCallables
     */
    public function testInSpanNamesAreSuccessfullyGenerated($sumCallable, $expectedName)
    {
        list($tracer, $flusher) = self::createDefaultTestTracer();

        $tracer->inSpan(
            $sumCallable,
            [1, 2]
        );

        $spans = $flusher();

        $span = $spans[0]->toArray();
        $this->assertEquals($expectedName, $span['name']);
    }

    public function sumCallables(): array
    {
        $anonymousSumer = new class() {
            public function sum(int $a, int $b)
            {
                return $a + $b;
            }

            public function __invoke(int $a, int $b)
            {
                return $this->sum($a, $b);
            }
        };

        $sumer = new Sumer();

        return [
            ['\ZipkinTests\Unit\InSpan\Callables\sum', 'sum'], // string
            [
                function (int $a, int $b) {
                    return $a + $b;
                }, ''
            ], // first class function
            ['\ZipkinTests\Unit\InSpan\Sumer::ssum', 'Sumer::ssum'],
            [[Sumer::class, 'ssum'], 'Sumer::ssum'],
            [[$sumer, 'sum'], 'Sumer::sum'],
            [[$anonymousSumer, 'sum'], 'sum'], // object method
            [$anonymousSumer, ''] // invokable object
        ];
    }

    public function testInSpanForFailingCall()
    {
        list($tracer, $flusher) = self::createDefaultTestTracer();

        $sum = new class() {
            public function __invoke(int $a, int $b)
            {
                throw new OutOfBoundsException('too small values');
            }
        };

        try {
            $tracer->inSpan($sum, [1, 2]);
            $this->fail('Should not reach here');
        } catch (OutOfBoundsException $e) {
        }

        $spans = $flusher();
        $this->assertCount(1, $spans);

        $span = $spans[0]->toArray();
        $this->assertEquals('too small values', $span['tags']['error']);
    }

    public function testJoinSpans()
    {
        $context = TraceContext::createAsRoot(DefaultSamplingFlags::createAsSampled());

        list($tracer, $flusher) = self::createDefaultTestTracer();

        $span = $tracer->joinSpan($context);
        $span->start();
        $span->finish();

        $spans = $flusher();

        $span = $spans[0]->toArray();
        $this->assertTrue($span['shared']);
    }

    private static function createDefaultTestTracer(): array
    {
        $reporter = new InMemory();
        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $reporter,
            BinarySampler::createAsAlwaysSample(),
            false,
            new CurrentTraceContext,
            false
        );

        return [
            $tracer,
            function () use ($tracer, $reporter): array {
                $tracer->flush();
                return $reporter->flush();
            },
        ];
    }
}
