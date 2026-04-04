<?php

namespace App\Models\Neo4j;

use Neo4jEloquent\Model;


class Subject extends Model
{
    protected array $labels    = ['Subject'];
    protected array $fillable  = ['name', 'code', 'description'];
}
