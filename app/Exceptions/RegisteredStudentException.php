<?php

namespace App\Exceptions;

use Exception;

class RegisteredStudentException extends Exception
{
    public function __construct($message = "Student is already registered in all provided subjects", $code = 400)
    {
        parent::__construct($message, $code);
    }

    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], $this->getCode());
    }
}
