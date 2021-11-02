<?php

declare(strict_types=1);

namespace ZipkinTests\Unit\Propagation;

use Zipkin\Propagation\TraceContext;
use Zipkin\Propagation\Map;
use Zipkin\Propagation\ExtraField;
use Zipkin\Propagation\B3;
use PHPUnit\Framework\TestCase;

final class ExtraFieldTest extends TestCase
{
    public function testGetKeys(): void
    {
        $propagation = new ExtraField(new B3(), ['x-a' => 'a']);
        $this->assertEquals(['x-a'], $propagation->getKeys());
    }

    public function testGetInjector(): void
    {
        $propagation = new ExtraField(new B3(), ['x-request-id' => 'request_id']);
        $injector = $propagation->getInjector(new Map());

        $carrier = [];
        $injector(TraceContext::createAsRoot()->withExtra(['request_id' => 'abc123']), $carrier);
        $this->assertEquals('abc123', $carrier['x-request-id']);
    }

    public function testGetExtractor(): void
    {
        $propagation = new ExtraField(new B3(), ['x-request-id' => 'request_id']);
        $extractor = $propagation->getExtractor(new Map());

        /**
         * @var $context TraceContext
         */
        $context = $extractor([
            'x-b3-traceid' => '7f46165474d11ee5836777d85df2cdab',
            'x-b3-spanid' => '4654d1e567d8f2ab',
            'x-request-id' => 'xyz987',
        ]);

        $this->assertEquals('xyz987', $context->getExtra()['request_id']);
    }
}
