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
        } finally {
            $server->stop();
        }
    }

    public function testHttpReportingSuccessWithExtraHeader()
    {
        $t = $this;

        $server = HttpTestServer::create(
            function (RequestInterface $request, ResponseInterface &$response) use ($t) {
                $t->assertEquals('POST', $request->getMethod());
                $t->assertEquals('application/json', $request->getHeader('Content-Type')[0]);
                $t->assertEquals('user@example.com', $request->getHeader('From')[0]);
                $response = $response->withStatus(202);
            }
        );

        $server->start();

        try {
            $curlClient = CurlFactory::create()->build([
                'endpoint_url' => $server->getUrl(),
                'headers' => ['From' => 'user@example.com', 'Content-Type' => 'test'],
            ]);

            $curlClient(json_encode([]));
        } finally {
            $server->stop();
        }
    }

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

    public function testHttpReportingSilentlySendTracesSuccess()
    {
        $t = $this;

        $server = HttpTestServer::create(
            function (RequestInterface $request, ResponseInterface &$response) use ($t) {
                $t->assertEquals('POST', $request->getMethod());
                $t->assertEquals('application/json', $request->getHeader('Content-Type')[0]);
                $response = $response->withStatus(202);
                $response->getBody()->write('Accepted');
            }
        );

        $server->start();

        try {
            $curlClient = CurlFactory::create()->build([
                'endpoint_url' => $server->getUrl(),
            ]);

            ob_start();
            $curlClient(json_encode([]));
        } finally {
            $server->stop();
            $output = ob_get_clean();
            $this->assertEmpty($output);
        }
    }

    public function testHttpReportingSilentlySendTracesFailure()
    {
        $server = HttpTestServer::create(
            function (RequestInterface $request, ResponseInterface &$response) {
                $response = $response->withStatus(404);
                $response->getBody()->write('Not Found');
            }
        );

        $server->start();

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Reporting of spans failed');

            $curlClient = CurlFactory::create()->build([
                'endpoint_url' => $server->getUrl(),
            ]);

            ob_start();
            $curlClient('');
        } finally {
            $server->stop();
            $output = ob_get_clean();
            $this->assertEmpty($output);
        }
    }
}
