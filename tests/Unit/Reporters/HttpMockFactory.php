<?php

namespace ZipkinTests\Unit\Reporters;

use RuntimeException;
use Zipkin\Reporters\Http\ClientFactory;

final class HttpMockFactory implements ClientFactory
{
    public const ERROR_MESSAGE = 'Failed to report over http.';

    /**
     * @var string
     */
    private $content;

    /**
     * @var bool
     */
    private $shouldFail;

    private function __construct($shouldFail)
    {
        $this->shouldFail = $shouldFail;
    }

    public static function createAsSuccess()
    {
        return new self(false);
    }

    public static function createAsFailing()
    {
        return new self(true);
    }

    /**
     * @param array $options
     * @return callable
     */
    public function build(array $options): callable
    {
        $self = $this;

        return function ($payload) use (&$self) {
            if ($self->shouldFail) {
                throw new RuntimeException(self::ERROR_MESSAGE);
            }

            $self->content = $payload;
        };
    }

    public function retrieveContent()
    {
        return $this->content;
    }
}
