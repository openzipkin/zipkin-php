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

namespace Zipkin\Instrumentation\Http\Client\Psr18;

use Zipkin\Instrumentation\Http\Client\Response as ClientResponse;
use Zipkin\Instrumentation\Http\Client\Request as ClientRequest;
use Psr\Http\Message\ResponseInterface;

final class Response extends ClientResponse
{
    /**
     * @var ResponseInterface
     */
    private $delegate;

    /**
     * @var Request|null
     */
    private $request;

    public function __construct(
        ResponseInterface $delegate,
        ?Request $request = null
    ) {
        $this->delegate = $delegate;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest(): ?ClientRequest
    {
        return $this->request;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode(): int
    {
        return $this->delegate->getStatusCode();
    }

    /**
     * {@inheritdoc}
     */
    public function unwrap(): ResponseInterface
    {
        return $this->delegate;
    }
}
