<?php

declare(strict_types=1);

namespace Zipkin;

final class SpanCustomizerShield implements SpanCustomizer
{
    /**
     * @var Span
     */
    private $delegate;

    /**
     * @var bool
     */
    private $isNotNoop = false;

    public function __construct(Span $span)
    {
        // If NOOP span we don't want to do the actual calls.
        if (!$span->isNoop()) {
            $this->delegate = $span;
            $this->isNotNoop = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): void
    {
        if ($this->isNotNoop) {
            $this->delegate->setName($name);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function tag(string $key, string $value): void
    {
        if ($this->isNotNoop) {
            $this->delegate->tag($key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function annotate(string $value, int $timestamp = null): void
    {
        if ($this->isNotNoop) {
            $this->delegate->annotate($value, $timestamp);
        }
    }
}
