<?php

declare(strict_types=1);

namespace Zipkin\Recording;

use Zipkin\Reporters\SpanSerializer;
use Zipkin\ErrorParser;
use Zipkin\Endpoint;
use Throwable;

interface ReadbackSpan
{
    public function getSpanId(): string;
    public function getTraceId(): string;
    public function getParentId(): ?string;
    public function isDebug(): bool;
    public function isShared(): bool;
    public function getName(): ?string;
    public function getKind(): ?string;
    public function getTimestamp(): int;
    public function getDuration(): int;
    public function getLocalEndpoint(): ?Endpoint;
    public function getTags(): array;
    public function getAnnotations(): array;
    public function getError(): ?Throwable;
    public function getRemoteEndpoint(): ?Endpoint;
}
