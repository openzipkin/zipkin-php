<?php

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
