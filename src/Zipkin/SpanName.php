<?php

namespace Zipkin\SpanName;

use Closure;

/**
 * Infers the span name based on the callable
 *
 * @var callable $fn
 */
function generateSpanName($fn): string
{
    $fnType = \gettype($fn);
    $name = '';
    if ($fnType === 'string') {
        $name = $fn;
    } elseif ($fnType === 'array') { // object->method call style
        if (\gettype($fn[0]) === 'string') { // static class
            $name = $fn[0] . '::' . $fn[1];
        } elseif (\strpos(\get_class($fn[0]), 'class@anonymous') !== 0) {
            $name = \get_class($fn[0]) . '::' . $fn[1]; // non anonymous class
        } else {
            $name = $fn[1]; // anonymous class, hence we use the method
        }
    } elseif ($fnType === 'object' && !($fn instanceof Closure)) { // invokable
        $fnClass = \get_class($fn);
        if (\strpos($fnClass, 'class@anonymous') !== 0) {
            $name = $fnClass;
        }
    }
    $namePieces = \explode("\\", $name);
    return $namePieces[\count($namePieces) - 1];
}
