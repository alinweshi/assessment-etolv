<?php

namespace App\Repositories\Eloquent;

use App\Models\School;
use App\Interfaces\SchoolRepositoryInterface;

class SchoolRepository implements SchoolRepositoryInterface
{
    public function all()
    {
        return School::paginate(10);
    }

    public function find($id)
    {
        return School::findOrFail($id);
    }

    public function create(array $data)
    {
        return School::create($data);
    }

    public function update($id, array $data)
    {
        $school = School::findOrFail($id);
        $school->update($data);
        return $school;
    }

    public function delete($id)
    {
        return School::destroy($id);
    }
}
