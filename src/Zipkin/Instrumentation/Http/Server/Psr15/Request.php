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

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server\Psr15;

use Zipkin\Instrumentation\Http\Server\Request as ServerRequest;
use Psr\Http\Message\RequestInterface;

final class Request extends ServerRequest
{
    /**
     * @var RequestInterface
     */
    private $delegate;

    public function __construct(RequestInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return $this->delegate->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): ?string
    {
        return $this->delegate->getUri()->getPath() ?: '/';
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl(): string
    {
        return $this->delegate->getUri()->__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader(string $name): ?string
    {
        return $this->delegate->getHeaderLine($name) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function unwrap(): RequestInterface
    {
        return $this->delegate;
    }
}
