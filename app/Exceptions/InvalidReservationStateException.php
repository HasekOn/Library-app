<?php

namespace App\Exceptions;

use Exception;

class InvalidReservationStateException extends Exception
{
    public function __construct(string $message = 'Invalid reservation state transition.')
    {
        parent::__construct($message);
    }
}
