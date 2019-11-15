<?php

declare(strict_types=1);

namespace Zipkin\Reporters;

use Zipkin\Reporter;
use Zipkin\Recording\Span;

final class InMemory implements Reporter
{
    /**
     * @var array|Span[]
     */
    private $spans = [];

    public function report(array $spans): void
    {
        $this->spans = \array_merge($this->spans, $spans);
    }

    /**
     * @return array|Span[]
     */
    public function flush(): array
    {
        $spans = $this->spans;
        $this->spans = [];
        return $spans;
    }
}
