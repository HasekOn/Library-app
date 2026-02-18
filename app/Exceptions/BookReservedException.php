<?php

namespace App\Exceptions;

use Exception;

class BookReservedException extends Exception
{
    public function __construct()
    {
        parent::__construct('This book is reserved by another user.');
    }
}
