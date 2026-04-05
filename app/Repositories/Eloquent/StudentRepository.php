<?php

namespace App\Repositories\Eloquent;

use App\Exceptions\RecordNotFoundException;
use App\Interfaces\StudentRepositoryInterface;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;

class StudentRepository implements StudentRepositoryInterface
{
    public function all()
    {
        return Student::with(['school', 'subjects'])->paginate(10);
    }

    public function find($id)
    {
        $student = Student::find($id);

        if (!$student) {
            throw new RecordNotFoundException();
        }

        return $student->load(['school', 'subjects']);
    }

    public function create(array $data)
    {
        return Student::create($data);
    }

    public function update($id, array $data)
    {
        $student = Student::find($id);
        if (!$student) {
            throw new RecordNotFoundException();
        }
        $student->update($data);
        return $student;
    }

    public function delete($id)
    {
        $student = Student::find($id);

        if (!$student) {
            throw new RecordNotFoundException('Student not found');
        }

        $student->delete();
    }



    public function enrollInSchool($studentId, $schoolId)
    {
        $student = Student::find($studentId);
        if (!$student) {
            throw new RecordNotFoundException('Student not found');
        }

        if (!School::where('id', $schoolId)->exists()) {
            throw new RecordNotFoundException('School not found');
        }

        $school = School::find($schoolId);
        $student->school()->associate($school);
        $student->save();

        return $student->load(['school', 'subjects']);
    }

    public function registerSubject($studentId, array $subjectIds = [])
    {
        $student = Student::find($studentId);
        if (!$student) {
            throw new RecordNotFoundException('Student not found');
        }

        $student->subjects()->syncWithoutDetaching($subjectIds);
        return $student->load('subjects', 'school');
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
    public function existsByEmail(string $email, ?string $exceptId = null): bool
    {
        return Student::where('email', $email)
            ->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }
    public function existsByPhone(string $phone, ?string $exceptId = null): bool
    {
        return Student::where('phone', $phone)
            ->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }
}
