<?php

declare(strict_types=1);

namespace ZipkinTests\Unit\Instrumentation\Http\Client\Psr18;

use Zipkin\Instrumentation\Http\Client\Psr18\Response as Psr18Response;
use Zipkin\Instrumentation\Http\Client\Psr18\Request;
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
        $response = new Psr18Response($delegateResponse, $request);
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
