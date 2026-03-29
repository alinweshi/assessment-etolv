<?php

namespace App\Models;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    /** @use HasFactory<\Database\Factories\SubjectFactory> */
    protected $fillable = ['name'];

    public function students()
    {
        return $this->belongsToMany(Student::class);
    }
}
