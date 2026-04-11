<?php

namespace App\Exceptions;

use Exception;

class DuplicateFieldException extends Exception
{
    public function __construct($message = "Duplicate field", $code = 409)
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
