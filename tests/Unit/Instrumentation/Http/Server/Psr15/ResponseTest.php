<?php

declare(strict_types=1);

namespace ZipkinTests\Unit\Instrumentation\Http\Server\Psr15;

use Zipkin\Instrumentation\Http\Server\Psr15\Response as Psr15Response;
use Zipkin\Instrumentation\Http\Server\Psr15\Request;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request as Psr7Request;

final class ResponseTest extends TestCase
{
    /**
     * @dataProvider delegateRequests
     */
    public function testResponseIsCreatedSuccessfully($request)
    {
        $delegateResponse = new Response(202);
        $response = new Psr15Response($delegateResponse, $request);
        $this->assertEquals(202, $response->getStatusCode());
        $this->assertSame($request, $response->getRequest());
        $this->assertSame($delegateResponse, $response->unwrap());
    }

    public function delegateRequests(): array
    {
        return [
            [null],
            [new Request(new Psr7Request('GET', 'http://test.com/path'))],
        ];
    }
}
