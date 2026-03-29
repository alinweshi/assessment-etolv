<?php

namespace App\Models;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    /** @use HasFactory<\Database\Factories\SchoolFactory> */
    use HasFactory, SoftDeletes;
    protected $fillable = ['name', 'address', 'phone', 'email', 'website'];

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
