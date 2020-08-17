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

declare(strict_types=1);

namespace Zipkin\Propagation;

/**
 * Used as an input to {@link Propagation#injector(Setter)} as a way
 * to support different injectors based on the kind of propagation
 * e.g. messaging uses B3 single value whereas client uses single and
 * multi values.
 */
interface RemoteSetter extends Setter
{
    public function getKind(): string;
}
