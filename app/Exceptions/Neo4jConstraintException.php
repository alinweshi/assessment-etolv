<?php

namespace App\Exceptions;

use Laudis\Neo4j\Exception\Neo4jException;

class Neo4jConstraintException
{
    // Neo4j error code for constraint violations
    private const CONSTRAINT_CODE = 'Neo.ClientError.Schema.ConstraintValidationFailed';

    public static function isConstraintViolation(Neo4jException $e): bool
    {
        return str_contains($e->getMessage(), self::CONSTRAINT_CODE);
    }

    public static function parseField(Neo4jException $e, array $fieldMap): string
    {
        $message = $e->getMessage();

        foreach ($fieldMap as $property => $label) {
            if (str_contains($message, $property)) {
                return $label;
            }
        }

        return 'A unique field';
    }
}
