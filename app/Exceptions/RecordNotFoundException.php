<?php

namespace App\Exceptions;

use Exception;

class RecordNotFoundException extends Exception
{
    public function __construct($message = "Record not found", $code = 404)
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
