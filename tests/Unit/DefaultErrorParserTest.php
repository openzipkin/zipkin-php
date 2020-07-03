<?php

namespace ZipkinTests\Unit;

use Zipkin\Recording\Span;
use Zipkin\Propagation\TraceContext;
use Zipkin\Endpoint;
use Zipkin\DefaultErrorParser;
use Throwable;
use RuntimeException;
use PHPUnit\Framework\TestCase;

final class DefaultErrorParserTest extends TestCase
{
    /**
     * @dataProvider throwables
     */
    public function testErrorIsParsedSuccessfully(Throwable $e, string $expectedValue)
    {
        $span = Span::createFromContext(TraceContext::createAsRoot(), Endpoint::createAsEmpty());

        $parser = new DefaultErrorParser;
        $tags = $parser->parseTags($e);
        $this->assertEquals($expectedValue, $tags['error']);
    }

    public function throwables(): array
    {
        return [
            'known exception' => [new DefaultErrorParserException, 'default error'],
            'std exception' => [new RuntimeException('runtime error'), 'runtime error'],
            'anonymous throwable' => [
                new class('anonymous error') extends RuntimeException {
                },
                'anonymous error',
            ],
        ];
    }
}
