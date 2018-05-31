<?php

namespace ZipkinTests\Integration\Reporters\Http;

use Exception;
use HttpTest\HttpTestServer;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Zipkin\Reporters\Http\CurlFactory;

final class CurlFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testHttpReportingSuccess()
    {
        $t = $this;

        $server = HttpTestServer::create(
            function (RequestInterface $request, ResponseInterface &$response) use ($t) {
                $t->assertEquals('POST', $request->getMethod());
                $t->assertEquals('application/json', $request->getHeader('Content-Type')[0]);
                $response = $response->withStatus(202);
            }
        );

        $server->start();

        try {
            $curlClient = CurlFactory::create()->build([
                'endpoint_url' => $server->getUrl(),
            ]);

            $curlClient(json_encode([]));
        } catch (Exception $e) {
            $server->stop();

            $this->fail($e->getMessage());
        } finally {
            $server->stop();
        }
    }

    /**
     * @requires PHP 7.0
     */
    public function testHttpReportingFailsDueToInvalidStatusCode()
    {
        $server = HttpTestServer::create(
            function (RequestInterface $request, ResponseInterface &$response) {
                $response = $response->withStatus(404);
            }
        );

        $server->start();

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Reporting of spans failed');

            $curlClient = CurlFactory::create()->build([
                'endpoint_url' => $server->getUrl(),
            ]);

            $curlClient('');

            $server->stop();

            $this->fail('Runtime exception expected');
        } finally {
            $server->stop();
        }
    }

    public function testHttpReportingFailsDueToUnreachableUrl()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Reporting of spans failed');

        $curlClient = CurlFactory::create()->build([
            'endpoint_url' => 'invalid_url',
        ]);

        $curlClient('');
    }
}
