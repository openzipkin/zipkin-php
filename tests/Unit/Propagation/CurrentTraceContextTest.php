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

namespace ZipkinTests\Unit\Propagation;

use PHPUnit\Framework\TestCase;
use Zipkin\Propagation\CurrentTraceContext;
use Zipkin\Propagation\TraceContext;

final class CurrentTraceContextTest extends TestCase
{
    public function testCurrentTraceContextIsCreated()
    {
        $context = TraceContext::createAsRoot();
        $currentTraceContext = new CurrentTraceContext($context);
        $this->assertEquals($context, $currentTraceContext->getContext());
    }

    /**
     * @dataProvider contextProvider
     */
    public function testNewScopeSuccess($context1)
    {
        $currentTraceContext = new CurrentTraceContext($context1);
        $context2 = TraceContext::createAsRoot();

        $scopeCloser = $currentTraceContext->createScopeAndRetrieveItsCloser($context2);
        $this->assertEquals($context2, $currentTraceContext->getContext());

        $scopeCloser();
        $this->assertEquals($context1, $currentTraceContext->getContext());

        /** Verifies idempotency */
        $scopeCloser();
        $this->assertEquals($context1, $currentTraceContext->getContext());
    }

    public function contextProvider()
    {
        return [
            [TraceContext::createAsRoot()],
            [null]
        ];
    }
}
