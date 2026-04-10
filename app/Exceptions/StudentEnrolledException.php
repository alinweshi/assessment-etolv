<?php

namespace App\Exceptions;

use Exception;

class StudentEnrolledException extends Exception
{
    public function __construct($message = "Student is already enrolled in the provided school", $code = 400)
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
