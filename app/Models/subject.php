<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;

class subject extends Model
{
    /** @use HasFactory<\Database\Factories\SubjectFactory> */
    protected $fillable = ['name'];

    public function students()
    {
        return $this->belongsToMany(Student::class);
    }
    use HasFactory;
}
