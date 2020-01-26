<?php

namespace ZipkinTests\Unit\InSpanCallables;

function sum(int $a, int $b): int
{
    return $a + $b;
}
