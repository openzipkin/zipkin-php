<?php

namespace Zipkin\Instrumentation\Mysqli;

use mysqli_result;
use const Zipkin\Tags\ERROR;
use Zipkin\Tracer;
use Zipkin\Span;
use Zipkin\Endpoint;
use Zipkin\Kind;
use InvalidArgumentException;

/**
 * Mysqli is an instrumented extension for Mysqli.
 * Function signatures come are borrowed from
 * https://github.com/php/php-src/blob/master/ext/mysqli/mysqli.stub.php
 */
final class Mysqli extends \Mysqli
{
    private const DEFAULT_OPTIONS = [
        'tag_query' => false,
        'remote_endpoint' => null,
        'default_tags' => [],
    ];

    private Tracer $tracer;
    private array $options;

    public function __construct(
        Tracer $tracer,
        array $options = [],
        ?string $host = null,
        ?string $user = null,
        ?string $password = null,
        string $database = '',
        ?int $port = null,
        ?string $socket = null
    ) {
        self::validateOptions($options);
        $this->tracer = $tracer;
        $this->options = $options + self::DEFAULT_OPTIONS;

        $defaultHost = \ini_get('mysqli.default_host') ?: '';
        $defaultUser = \ini_get('mysqli.default_user') ?: '';
        $defaultPassword = \ini_get('mysqli.default_pw') ?: '';
        $defaultPort = (int) (\ini_get('mysqli.default_port') ?: 3306);
        $defaultSocket = \ini_get('mysqli.default_socket') ?: '';

        parent::__construct(
            $host ?? $defaultHost,
            $user ?? $defaultUser,
            $password ?? $defaultPassword,
            $database,
            $port ?? $defaultPort,
            $socket ?? $defaultSocket
        );
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, int $result_mode = MYSQLI_STORE_RESULT): mysqli_result|bool
    {
        $span = $this->tracer->nextSpan();
        $span->start();
        $span->setName('query');
        $span->setKind(Kind\CLIENT);

        if ($this->options['tag_query']) {
            $span->tag('sql.query', $query);
        }

        foreach ($this->options['default_tags'] as $key => $value) {
            $span->tag($key, $value);
        }

        if ($this->options['remote_endpoint'] !== null) {
            $span->setRemoteEndpoint($this->options['remote_endpoint']);
        }

        try {
            $result = parent::query($query, $result_mode);
            if ($result === false) {
                $span->tag(ERROR, $this->error);
            }
            return $result;
        } catch (\Throwable $e) {
            $span->tag(ERROR, $e->getMessage());
            throw $e;
        } finally {
            $span->finish();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function real_query(string $query): bool
    {
        $span = $this->tracer->nextSpan();
        $span->start();
        $span->setName('real_query');
        $span->setKind(Kind\CLIENT);

        if ($this->options['tag_query']) {
            $span->tag('sql.query', $query);
        }

        foreach ($this->options['default_tags'] as $key => $value) {
            $span->tag($key, $value);
        }

        if ($this->options['remote_endpoint'] !== null) {
            $span->setRemoteEndpoint($this->options['remote_endpoint']);
        }

        try {
            $result = parent::real_query($query);
            if ($result === false) {
                $span->tag(ERROR, $this->error);
            }
            return $result;
        } catch (\Throwable $e) {
            $span->tag(ERROR, $e->getMessage());
            throw $e;
        } finally {
            $span->finish();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function begin_transaction(int $flags = 0, ?string $name = null): bool
    {
        $span = $this->tracer->nextSpan();
        $span->start();
        $span->setName('begin_transaction');
        $span->setKind(Kind\CLIENT);

        foreach ($this->options['default_tags'] as $key => $value) {
            $span->tag($key, $value);
        }

        if ($this->options['remote_endpoint'] !== null) {
            $span->setRemoteEndpoint($this->options['remote_endpoint']);
        }

        try {
            $result = parent::begin_transaction($flags, $name);
            if ($result === false) {
                $span->tag(ERROR, $this->error);
            }
            return $result;
        } catch (\Throwable $e) {
            $span->tag(ERROR, $e->getMessage());
            throw $e;
        } finally {
            $span->finish();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function commit(int $flags = 0, ?string $name = null): bool
    {
        $span = $this->tracer->nextSpan();
        $span->start();
        $span->setName('commit');
        $span->setKind(Kind\CLIENT);

        foreach ($this->options['default_tags'] as $key => $value) {
            $span->tag($key, $value);
        }

        if ($this->options['remote_endpoint'] !== null) {
            $span->setRemoteEndpoint($this->options['remote_endpoint']);
        }

        try {
            $result = parent::commit($flags, $name);
            if ($result === false) {
                $span->tag(ERROR, $this->error);
            }
            return $result;
        } catch (\Throwable $e) {
            $span->tag(ERROR, $e->getMessage());
            throw $e;
        } finally {
            $span->finish();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(int $flags = 0, ?string $name = null): bool
    {
        $span = $this->tracer->nextSpan();
        $span->start();
        $span->setName('rollback');
        $span->setKind(Kind\CLIENT);

        foreach ($this->options['default_tags'] as $key => $value) {
            $span->tag($key, $value);
        }

        if ($this->options['remote_endpoint'] !== null) {
            $span->setRemoteEndpoint($this->options['remote_endpoint']);
        }

        try {
            $result = parent::rollback($flags, $name);
            if ($result === false) {
                $span->tag(ERROR, $this->error);
            }
            return $result;
        } catch (\Throwable $e) {
            $span->tag(ERROR, $e->getMessage());
            throw $e;
        } finally {
            $span->finish();
        }
    }

    private static function validateOptions(array $opts): void
    {
        if (isset($opts['remote_endpoint']) && !$opts['remote_endpoint'] instanceof Endpoint) {
            throw new InvalidArgumentException(
                \sprintf('Invalid remote_endpoint. Expected Endpoint, got %s', \gettype($opts['remote_endpoint']))
            );
        }

        if (isset($opts['default_tags']) && !\is_array($opts['default_tags'])) {
            throw new InvalidArgumentException(
                \sprintf('Invalid default_tags. Expected array, got %s', \gettype($opts['default_tags']))
            );
        }
    }
}
