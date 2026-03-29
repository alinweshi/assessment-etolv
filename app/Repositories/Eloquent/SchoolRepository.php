<?php

namespace App\Repositories\Eloquent;

use App\Exceptions\RecordNotFoundException;
use App\Interfaces\SchoolRepositoryInterface;
use App\Models\School;

class SchoolRepository implements SchoolRepositoryInterface
{
    public function all()
    {
        return School::paginate(10);
    }

    public function find($id)
    {
        $school = School::find($id);

        if (!$school) {
            throw new RecordNotFoundException('School not found');
        }

        return $school;
    }

    public function create(array $data)
    {
        return School::create($data);
    }

    public function update($id, array $data)
    {
        $school = School::find($id);
        if (!$school) {
            throw new RecordNotFoundException('School not found');
        }
        $school->update($data);
        return $school;
    }

    public function delete($id)
    {
        $student = School::find($id);

        if (!$student) {
            throw new RecordNotFoundException('School not found');
        }

        $student->delete();
    }
}
