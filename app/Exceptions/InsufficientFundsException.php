<?php

namespace App\Exceptions;

use Exception;

class InsufficientFundsException extends Exception
{
    public function __construct()
    {
        parent::__construct('The client does not have enough cash for this transaction.');
    }

}
