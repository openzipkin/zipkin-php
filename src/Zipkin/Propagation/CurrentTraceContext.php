<?php

declare(strict_types=1);

namespace Zipkin\Propagation;

/**
 * This makes a given span the current span by placing it in scope.
 *
 * <p>This type is an SPI, and intended to be used by implementors looking to change thread-local
 * storage, or integrate with other contexts such as logging (MDC).
 *
 * <h3>Design</h3>
 *
 * This design was inspired by com.google.instrumentation.trace.ContextUtils,
 * com.google.inject.servlet.RequestScoper and com.github.kristofa.brave.CurrentSpan
 */

final class CurrentTraceContext
{
    private ?TraceContext $context;

    public function __construct(TraceContext $currentContext = null)
    {
        $this->context = $currentContext;
    }

    /**
     * Returns the current span context in scope or null if there isn't one.
     */
    public function getContext(): ?TraceContext
    {
        return $this->context;
    }

    /**
     * Sets the current span in scope until the returned callable is called. It is a programming
     * error to drop or never close the result.
     *
     * @param TraceContext|null $currentContext
     * @return callable():void The scope closed
     */
    public function createScopeAndRetrieveItsCloser(TraceContext $currentContext = null): callable
    {
        $previous = $this->context;
        $self = $this;
        $this->context = $currentContext;

        return static function () use ($previous, $self): void {
            $self->context = $previous;
        };
    }
}
