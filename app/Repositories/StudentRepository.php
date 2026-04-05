<?php

namespace App\Repositories;

use App\Exceptions\RecordNotFoundException;
use App\Interfaces\StudentRepositoryInterface;
use App\Models\Neo4j\School;

class StudentRepository implements StudentRepositoryInterface
{
    /**
     * @param string $model Eloquent or Neo4jEloquent model class
     */
    public function __construct(protected string $model) {}

    public function all()
    {
        return $this->model::paginate(10);
    }

    public function find($id)
    {
        $student = $this->model::find($id);

        if (!$student) {
            throw new RecordNotFoundException("Student with id $id not found");
        }

        return $student;
    }

    public function findByEmail($email)
    {
        return $this->model::where('email', $email)->first();
    }

    public function create(array $data)
    {

        $student = $this->model::create($data);

        return $student->load('school');
    }

    public function update($id, array $data)
    {
        $student = $this->find($id);
        $student->update($data);

        return $student;
    }

    public function delete($id)
    {
        $student = $this->find($id);
        $student->delete();
    }
    public function enrollInSchool($studentId, $schoolId)
    {
        $student = $this->find($studentId);

        // This creates a (Student)-[:STUDENT_OF]->(School) relationship in Neo4j
        return $student->school()->associate(School::find($schoolId))->save();
    }

    public function registerSubject($studentId, array $subjectIds = [])
    {
        $student = $this->find($studentId);

        // Sync handles adding new relationships and removing ones not in the array
        // Maps to (Student)-[:ENROLLED_IN]->(Subject)
        return $student->subjects()->sync($subjectIds);
    }

    public function report()
    {
        // Example: Get students with their related school and subjects count
        return $this->model::with(['school', 'subjects'])->paginate(15);
    }
}
