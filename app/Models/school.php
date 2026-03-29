<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;

class school extends Model
{
    /** @use HasFactory<\Database\Factories\SchoolFactory> */
    use HasFactory;
    protected $fillable = ['name', 'address', 'phone', 'email', 'website'];

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
