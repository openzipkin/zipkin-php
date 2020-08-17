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

namespace Zipkin\Instrumentation\Http\Server\Psr15\Propagation;

use Zipkin\Propagation\RequestHeaders as BaseRequestHeaders;
use Zipkin\Propagation\RemoteSetter;
use Zipkin\Kind;

final class RequestHeaders extends BaseRequestHeaders implements RemoteSetter
{
    public function getKind(): string
    {
        return Kind\SERVER;
    }
}
