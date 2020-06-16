<?php

namespace iggyvolz\phppreproccessor;

use Exception;

class PreprocessorException extends Exception
{
    public function __construct(string $message, string $file, int $line)
    {
        parent::__construct("$message ($file:$line)");
    }
}