<?php

namespace App\Traits;

trait HasMeta
{
    public static function buildMeta(int $page, int $limit, int $total): array
    {
        return [
            'current_page' => $page,
            'per_page'     => $limit,
            'total_count'  => $total,
            'last_page'    => (int) ceil($total / $limit),
        ];
    }
}
