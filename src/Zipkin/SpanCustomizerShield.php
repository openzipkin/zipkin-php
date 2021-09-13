<?php

declare(strict_types=1);

namespace Zipkin;

/**
 * SpanCustomizerShield is a simple implementation of SpanCustomizer.
 * It is highly recommended to not to wrap a NOOP span as it will only
 * add overhead for no benefit.
 */
final class SpanCustomizerShield implements SpanCustomizer
{
    private Span $delegate;

    public function __construct(Span $span)
    {
        $this->delegate = $span;
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): void
    {
        $this->delegate->setName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function tag(string $key, string $value): void
    {
        $this->delegate->tag($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function annotate(string $value, int $timestamp = null): void
    {
        $this->delegate->annotate($value, $timestamp);
    }
}
