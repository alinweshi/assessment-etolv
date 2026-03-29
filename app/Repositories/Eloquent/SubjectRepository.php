<?php

namespace App\Repositories\Eloquent;

use App\Exceptions\RecordNotFoundException;
use App\Interfaces\SubjectRepositoryInterface;
use App\Models\Subject;

class SubjectRepository implements SubjectRepositoryInterface
{
    public function all()
    {
        return Subject::paginate(10);
    }

    public function find($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            throw new RecordNotFoundException('Subject not found');
        }

        return $subject;
    }

    public function create(array $data)
    {
        return Subject::create($data);
    }

    public function update($id, array $data)
    {
        $subject = Subject::find($id);
        if (!$subject) {
            throw new RecordNotFoundException('Subject not found');
        }
        $subject->update($data);
        return $subject;
    }

    public function delete($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            throw new RecordNotFoundException('Subject not found');
        }

        $subject->delete();
    }
}
