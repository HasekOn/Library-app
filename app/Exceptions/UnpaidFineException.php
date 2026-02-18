<?php

namespace App\Exceptions;

use Exception;

class UnpaidFineException extends Exception
{
    public function __construct()
    {
        parent::__construct('User has unpaid fines and cannot borrow books.');
    }
}
