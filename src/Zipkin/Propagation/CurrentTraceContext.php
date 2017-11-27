<?php

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
    private $context;

    private function __construct(TraceContext $currentContext = null)
    {
        $this->context = $currentContext;
    }

    /**
     * Creates a current trace context controller. If there is no context, and consequently no span, it holds null.
     *
     * @param TraceContext|null $currentContext
     * @return CurrentTraceContext
     */
    public static function create(TraceContext $currentContext = null)
    {
        return new self($currentContext);
    }

    /**
     * Returns the current span context in scope or null if there isn't one.
     *
     * @return TraceContext|null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sets the current span in scope until the returned callable is called. It is a programming
     * error to drop or never close the result.
     *
     * @param TraceContext|null $currentContext
     * @return callable The scope closed
     */
    public function createScopeAndRetrieveItsCloser(TraceContext $currentContext = null)
    {
        $previous = $this->context;
        $self = $this;
        $this->context = $currentContext;

        return function () use ($previous, $self) {
            $self->context = $previous;
        };
    }
}
