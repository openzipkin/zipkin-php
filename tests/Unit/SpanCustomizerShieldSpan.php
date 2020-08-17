<?php

/**
 * Copyright 2020 OpenZipkin Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace ZipkinTests\Unit;

use Zipkin\Propagation\TraceContext;
use Zipkin\Endpoint;
use Throwable;
use PHPUnit\Framework\TestCase;
use LogicException;

trait SpanCustomizerShieldSpan
{
    /**
     * @var TestCase
     */
    private $test;

    /**
     * @var TraceContext
     */
    private $context;

    public function __construct(TestCase $test)
    {
        $this->test = $test;
        $this->context = TraceContext::createAsRoot();
    }

    public function isNoop(): bool
    {
        return false;
    }

    public function getContext(): TraceContext
    {
        return $this->context;
    }

    public function start(int $timestamp = null): void
    {
        throw new LogicException('should not be called');
    }

    public function setKind(string $kind): void
    {
        throw new LogicException('should not be called');
    }

    public function setError(Throwable $e): void
    {
        throw new LogicException('should not be called');
    }

    public function setRemoteEndpoint(Endpoint $remoteEndpoint): void
    {
        throw new LogicException('should not be called');
    }

    public function abandon(): void
    {
        throw new LogicException('should not be called');
    }

    public function finish(int $timestamp = null): void
    {
        throw new LogicException('should not be called');
    }

    public function flush(): void
    {
        throw new LogicException('should not be called');
    }
}
