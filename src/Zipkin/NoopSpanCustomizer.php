<?php

declare(strict_types=1);

namespace Zipkin;

final class NoopSpanCustomizer implements SpanCustomizer
{
    /**
     * {@inheritdoc}
     */
    public function setName(string $name): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function tag(string $key, string $value): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function annotate(string $value, ?int $timestamp = null): void
    {
    }
}
