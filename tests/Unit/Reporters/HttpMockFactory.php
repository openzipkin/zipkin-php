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

namespace ZipkinTests\Unit\Reporters;

use Zipkin\Reporters\Http\ClientFactory;
use RuntimeException;

final class HttpMockFactory implements ClientFactory
{
    public const ERROR_MESSAGE = 'Failed to report over http.';

    /**
     * @var string
     */
    private $content;

    /**
     * @var int
     */
    private $calledTimes = 0;

    /**
     * @var bool
     */
    private $shouldFail;

    private function __construct(bool $shouldFail)
    {
        $this->shouldFail = $shouldFail;
    }

    public static function createAsSuccess(): self
    {
        return new self(false);
    }

    public static function createAsFailing(): self
    {
        return new self(true);
    }

    /**
     * @param array $options
     * @return callable(string):void
     */
    public function build(array $options): callable
    {
        $self = $this;

        return function (string $payload) use (&$self): void {
            if ($self->shouldFail) {
                throw new RuntimeException(self::ERROR_MESSAGE);
            }

            $self->calledTimes += 1;
            $self->content = $payload;
        };
    }

    public function retrieveContent(): string
    {
        return $this->content;
    }

    public function calledTimes(): int
    {
        return $this->calledTimes;
    }
}
