<?php

namespace App\Exceptions;

use Exception;

class InvalidLoanStateException extends Exception
{
    public function __construct(string $message = 'Invalid loan state transition.')
    {
        parent::__construct($message);
    }
}
