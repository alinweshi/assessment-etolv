<?php

namespace App\Repositories\Eloquent;

use App\Models\Subject;
use App\Interfaces\SubjectRepositoryInterface;

class SubjectRepository implements SubjectRepositoryInterface
{
    public function all()
    {
        return Subject::paginate(10);
    }

    public function find($id)
    {
        return Subject::findOrFail($id);
    }

    public function create(array $data)
    {
        return Subject::create($data);
    }

    public function update($id, array $data)
    {
        $subject = Subject::findOrFail($id);
        $subject->update($data);
        return $subject;
    }

    public function delete($id)
    {
        return Subject::destroy($id);
    }
}
