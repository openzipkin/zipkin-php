<?php

namespace ZipkinTests\Unit\Propagation;

use PHPUnit_Framework_TestCase;
use Zipkin\Propagation\CurrentTraceContext;
use Zipkin\Propagation\TraceContext;

final class CurrentTraceContextTest extends PHPUnit_Framework_TestCase
{
    public function testCurrentTraceContextIsCreated()
    {
        $context = TraceContext::createAsRoot();
        $currentTraceContext = CurrentTraceContext::create($context);
        $this->assertEquals($context, $currentTraceContext->getContext());
    }

    /**
     * @dataProvider contextProvider
     */
    public function testNewScopeSuccess($context1)
    {
        $currentTraceContext = CurrentTraceContext::create($context1);
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
            [ TraceContext::createAsRoot() ],
            [ null ]
        ];
    }
}
