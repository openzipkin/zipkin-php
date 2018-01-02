<?php

namespace Zipkin\Reporters\Http;

interface ClientFactory
{
    /**
     * @param array $options
     * @return callable
     */
    public function build(array $options);
}
