<?php

declare(strict_types=1);

namespace Zipkin\Reporters\Http;

interface ClientFactory
{
    /**
     * @param array $options
     * @return callable
     */
    public function build(array $options): callable;
}
