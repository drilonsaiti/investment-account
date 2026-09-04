<?php

namespace App\Exceptions;

use Exception;

class InsufficientHoldingsException extends Exception
{
    public function __construct(string $instrument, int $owned, int $requested)
    {
        parent::__construct(
            "The client owns only {$owned} units of {$instrument}, but the requested sale is for {$requested}."
        );
    }
}
