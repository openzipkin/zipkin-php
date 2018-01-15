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
    /**
     * @requires PHP 7.0
     */
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

        $pid = pcntl_fork();

        if ($pid === -1) {
            $this->fail('Error forking thread.');
        } elseif ($pid) {
            $server->start();
        } else {
            $server->waitForReady();

            try {
                $curlClient = CurlFactory::create()->build([
                    'endpoint_url' => $server->getUrl(),
                ]);

                $curlClient(json_encode([]));
            } catch (Exception $e) {
                $this->fail($e->getMessage());
            } finally {
                $server->stop();
            }
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

        $pid = pcntl_fork();

        if ($pid === -1) {
            $this->fail('Error forking thread.');
        } elseif ($pid) {
            $server->start();
        } else {
            $server->waitForReady();

            try {
                $this->expectException(RuntimeException::class);
                $this->expectExceptionMessage('Reporting of spans failed');

                $curlClient = CurlFactory::create()->build([
                    'endpoint_url' => $server->getUrl(),
                ]);

                $curlClient('');

                $this->fail('Runtime exception expected');
            } finally {
                $server->stop();
            }
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
