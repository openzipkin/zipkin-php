<?php

namespace ZipkinTests\Unit;

use Exception;

final class DefaultErrorParserException extends Exception
{
    public function __construct()
    {
        $this->message = 'default error';
    }
}
