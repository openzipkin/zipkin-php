<?php

/**
 * Copyright 2020 OpenZipkin Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Zipkin\SpanName;

use Closure;

/**
 * Infers the span name based on the callable.
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
