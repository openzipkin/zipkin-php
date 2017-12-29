<?php

namespace ZipkinTests\Unit\Reporters;

use Zipkin\Reporters\Http\ClientFactory;

final class HttpMockFactory implements ClientFactory
{
    private $content;

    /**
     * @param array $options
     * @return callable
     */
    public function build(array $options)
    {
        $self = $this;

        return function ($payload) use ($self) {
            $self->content = $payload;
        };
    }

    public function retrieveContent()
    {
        return $this->content;
    }
}
