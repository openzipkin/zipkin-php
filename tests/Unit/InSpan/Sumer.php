<?php

namespace ZipkinTests\Unit\InSpan;

class Sumer
{
    public function sum(int $a, int $b): int
    {
        return $a + $b;
    }

    public static function ssum(int $a, int $b): int
    {
        return $a + $b;
    }
}
