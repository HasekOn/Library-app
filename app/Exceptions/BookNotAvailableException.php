<?php

namespace App\Exceptions;

use Exception;

class BookNotAvailableException extends Exception
{
    public function __construct()
    {
        parent::__construct('This book has no available copies.');
    }
}
