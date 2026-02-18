<?php

namespace App\Exceptions;

use Exception;

class BookAvailableForLoanException extends Exception
{
    public function __construct()
    {
        parent::__construct('This book is available for loan, no need to reserve.');
    }
}
