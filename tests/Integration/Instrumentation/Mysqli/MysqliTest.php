<?php

declare(strict_types=1);

namespace ZipkinTests\Integration\Instrumentation\Mysqli;

use Zipkin\Tracer;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Reporters\InMemory;
use Zipkin\Propagation\CurrentTraceContext;
use Zipkin\Instrumentation\Mysqli\Mysqli;
use Zipkin\Endpoint;
use Prophecy\PhpUnit\ProphecyTrait;
use PHPUnit\Framework\TestCase;

final class MysqliTest extends TestCase
{
    use ProphecyTrait;

    private static function launchMySQL(): array
    {
        shell_exec('docker rm -f zipkin_php_mysql_test');
        shell_exec(sprintf('cd %s; docker compose up -d', __DIR__));
        echo "Waiting for MySQL container to be up.\n";
        while (true) {
            $res = shell_exec('docker ps --filter "name=zipkin_php_mysql_test" --format "{{.Status}}"');
            usleep(500000);
            if ($res !== null && strpos($res, "healthy") !== false) {
                echo "MySQL container is up.\n";
                break;
            }
        }

        $host = '127.0.0.1';
        $user = 'root';
        $pass = 'root';
        $db = 'test';
        $port = 3306;

        return [[$host, $user, $pass, $db, $port], function () {
            shell_exec(sprintf('cd %s; docker compose stop', __DIR__));
        }];
    }

    public function testConnect()
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            $this->markTestSkipped("Running the test on non-Linux systems might be problematic");
        }

        if (!extension_loaded("mysqli")) {
            $this->markTestSkipped("mysqli isn't loaded");
        }

        list($params, $closer) = self::launchMySQL();

        $reporter = new InMemory();

        $tracer = new Tracer(
            Endpoint::createAsEmpty(),
            $reporter,
            BinarySampler::createAsAlwaysSample(),
            false, // usesTraceId128bits
            new CurrentTraceContext(),
            false // isNoop
        );

        try {
            $mysqli = new Mysqli($tracer, [], ...$params);

            if ($mysqli->connect_errno) {
                $this->fail(
                    sprintf('Failed to connect to MySQL: %s %s', $mysqli->connect_errno, $mysqli->connect_error)
                );
            }

            $res = $mysqli->query('SELECT 1');
            $this->assertEquals(1, $res->num_rows);

            $tracer->flush();
            $spans = $reporter->flush();
            $this->assertEquals(1, count($spans));

            $span = $spans[0];
            $this->assertEquals('sql/query', $span->getName());
        } finally {
            $closer();
        }
    }
}
