<?php

namespace App\Models\Neo4j;

use Neo4jEloquent\Model;

class School extends Model
{
    protected array $labels    = ['School'];
    protected array $fillable  = ['name', 'address', 'phone', 'email', 'website'];
}
