<?php

namespace App\Repositories\Eloquent;

use App\Models\Student;
use App\Interfaces\StudentRepositoryInterface;

class StudentRepository implements StudentRepositoryInterface
{
    public function all()
    {
        return Student::with(['school', 'subjects'])->paginate(10);
    }

    public function find($id)
    {
        return Student::with(['school', 'subjects'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return Student::create($data);
    }

    public function update($id, array $data)
    {
        $student = Student::findOrFail($id);
        $student->update($data);
        return $student;
    }

    public function delete($id)
    {
        return Student::destroy($id);
    }

    public function enrollInSchool($studentId, $schoolId)
    {
        $student = Student::findOrFail($studentId);
        $student->school()->associate($schoolId);
        $student->save();
        return $student->load('school');
    }

    public function registerSubject($studentId, $subjectId)
    {
        $student = Student::findOrFail($studentId);
        $student->subjects()->syncWithoutDetaching([$subjectId]);
        return $student->load('subjects');
    }

    public function report()
    {
        return Student::with(['school', 'subjects'])
            ->get()
            ->map(fn($s) => [
                'student'  => $s->name,
                'school'   => $s->school?->name,
                'subjects' => $s->subjects->pluck('name'),
            ]);
    }
}
