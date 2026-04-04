<?php

namespace App\Models\Neo4j;

use Neo4jEloquent\Model;

class Student extends Model
{
    protected array $labels    = ['Student'];
    protected array $fillable  = ['name', 'age', 'email', 'phone'];
}
