<?php

namespace App\Traits;

use Laudis\Neo4j\Types\Node;

trait MapsNode
{
    // Each repository defines its own fields
    abstract protected static function fields(): array;

    public static function nodeToArray(Node $node): array
    {
        $result = [];

        foreach (static::fields() as $field) {
            $result[$field] = $node->getProperty($field, null);
        }

        return $result;
    }
}
