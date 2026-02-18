<?php

namespace App\Exceptions;

use Exception;

class MaxLoansExceededException extends Exception
{
    public function __construct()
    {
        parent::__construct('User has reached the maximum number of active loans (3).');
    }
}
