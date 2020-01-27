<?php

namespace ZipkinTests\Unit\InSpan\Callables;

function sum(int $a, int $b): int
{
    return $a + $b;
}
