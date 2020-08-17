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

use Zipkin\Recording\Span;
use Zipkin\Propagation\TraceContext;
use Zipkin\Endpoint;
use Zipkin\DefaultErrorParser;
use Throwable;
use RuntimeException;
use PHPUnit\Framework\TestCase;

final class DefaultErrorParserTest extends TestCase
{
    /**
     * @dataProvider throwables
     */
    public function testErrorIsParsedSuccessfully(Throwable $e, string $expectedValue)
    {
        $span = Span::createFromContext(TraceContext::createAsRoot(), Endpoint::createAsEmpty());

        $parser = new DefaultErrorParser;
        $tags = $parser->parseTags($e);
        $this->assertEquals($expectedValue, $tags['error']);
    }

    public function throwables(): array
    {
        return [
            'known exception' => [new DefaultErrorParserException, 'default error'],
            'std exception' => [new RuntimeException('runtime error'), 'runtime error'],
            'anonymous throwable' => [
                new class ('anonymous error') extends RuntimeException
                {
                },
                'anonymous error',
            ],
        ];
    }
}
