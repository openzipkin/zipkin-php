<?php

namespace Zipkin\Instrumentation\Mysqli;

use mysqli_result;
use Zipkin\Tracer;
use Zipkin\Span;
use Zipkin\Endpoint;

use InvalidArgumentException;
use const Zipkin\Tags\ERROR;

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
        string $host = null,
        string $user = null,
        string $password = null,
        string $database = '',
        int $port = null,
        string $socket = null
    ) {
        self::validateOptions($options);
        $this->tracer = $tracer;
        $this->options = $options + self::DEFAULT_OPTIONS;
        parent::__construct(
            $host ?? (ini_get('mysqli.default_host') ?: ''),
            $user ?? (ini_get('mysqli.default_user') ?: ''),
            $password ?? (ini_get('mysqli.default_pw') ?: ''),
            $database,
            $port ?? (($defaultPort = ini_get('mysqli.default_port')) ? (int) $defaultPort : 3306),
            $socket ?? (ini_get('mysqli.default_socket') ?: '')
        );
    }

    private static function validateOptions(array $options): void
    {
        if (array_key_exists('tag_query', $options) && ($options['tag_query'] !== (bool) $options['tag_query'])) {
            throw new InvalidArgumentException('Invalid tag_query, bool expected');
        }

        if (array_key_exists('remote_endpoint', $options) && !($options['remote_endpoint'] instanceof Endpoint)) {
            throw new InvalidArgumentException(sprintf('Invalid remote_endpoint, %s expected', Endpoint::class));
        }

        if (array_key_exists('default_tags', $options)
            && ($options['default_tags'] !== (array) $options['default_tags'])
        ) {
            throw new InvalidArgumentException('Invalid default_tags, array expected');
        }
    }

    private function addsTagsAndRemoteEndpoint(Span $span, string $query = null): void
    {
        if ($query !== null && $this->options['tag_query']) {
            $span->tag('sql.query', $query);
        }

        if ($this->options['remote_endpoint'] !== null) {
            $span->setRemoteEndpoint($this->options['remote_endpoint']);
        }

        foreach ($this->options['default_tags'] as $key => $value) {
            $span->tag($key, $value);
        }
    }

    /**
     * Performs a query on the database
     *
     * @return mysqli_result|bool
     * @alias mysqli_query
     */
    public function query(string $query, int $resultmode = MYSQLI_STORE_RESULT)
    {
        if ($resultmode === MYSQLI_ASYNC) {
            // if $resultmode is async, making the timing on this execution
            // does not make much sense. For now we just skip tracing on this.
            return parent::query($query, $resultmode);
        }

        $span = $this->tracer->nextSpan();
        $span->setName('sql/query');
        $this->addsTagsAndRemoteEndpoint($span, $query);
        if ($this->options['tag_query']) {
            $span->tag('sql.query', $query);
        }

        $span->start();
        try {
            $result = parent::query($query, $resultmode);
            if ($result === false) {
                $span->tag(ERROR, 'true');
            }
            return $result;
        } finally {
            $span->finish();
        }
    }

    /**
     * @return bool
     * @alias mysqli_real_query
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    public function real_query(string $query)
    {
        $span = $this->tracer->nextSpan();
        $span->setName('sql/query');
        $this->addsTagsAndRemoteEndpoint($span, $query);

        $span->start();
        try {
            $result = parent::real_query($query);
            if ($result === false) {
                $span->tag(ERROR, 'true');
            }
            return $result;
        } finally {
            $span->finish();
        }
    }

    /**
     * @return bool
     * @alias mysqli_begin_transaction
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    public function begin_transaction(int $flags = 0, ?string $name = null)
    {
        $span = $this->tracer->nextSpan();
        $span->setName('sql/begin_transaction');
        $this->addsTagsAndRemoteEndpoint($span);
        $span->start();
        if ($name !== null) {
            $span->tag('mysqli.transaction_name', $name);
        }
        try {
            $result = parent::begin_transaction($flags, $name);
            if ($result === false) {
                $span->tag(ERROR, 'true');
            }
            return $result;
        } finally {
            $span->finish();
        }
    }

    /**
     * @return bool
     * @alias mysqli_commit
     */
    public function commit(int $flags = -1, ?string $name = null)
    {
        $span = $this->tracer->nextSpan();
        $span->setName('sql/begin_transaction');
        $this->addsTagsAndRemoteEndpoint($span);
        $span->start();
        if ($name !== null) {
            $span->tag('mysqli.transaction_name', $name);
        }
        try {
            $result = parent::commit($flags, $name);
            if ($result === false) {
                $span->tag(ERROR, 'true');
            }
            return $result;
        } finally {
            $span->finish();
        }
    }

    /**
     * @return bool
     * @alias mysqli_rollback
     */
    public function rollback(int $flags = 0, ?string $name = null)
    {
        $span = $this->tracer->nextSpan();
        $span->setName('sql/rollback');
        $this->addsTagsAndRemoteEndpoint($span);
        $span->start();
        if ($name !== null) {
            $span->tag('mysqli.transaction_name', $name);
        }
        try {
            $result = parent::commit($flags, $name);
            if ($result === false) {
                $span->tag(ERROR, 'true');
            }
            return $result;
        } finally {
            $span->finish();
        }
    }
}
