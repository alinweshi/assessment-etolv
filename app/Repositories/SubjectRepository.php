<?php

namespace App\Repositories;


use App\Exceptions\RecordNotFoundException;
use App\Interfaces\SubjectRepositoryInterface;

class SubjectRepository implements SubjectRepositoryInterface
{
    /**
     * @param string $model NeoEloquent Subject model class
     */
    public function __construct(protected string $model) {}

    public function all()
    {
        return $this->model::paginate(10);
    }

    public function find($id)
    {
        $subject = $this->model::find($id);

        if (!$subject) {
            throw new RecordNotFoundException("Subject with id $id not found");
        }

        return $subject;
    }

    public function create(array $data)
    {
        return $this->model::create($data);
    }

    public function update($id, array $data)
    {
        $subject = $this->find($id);
        $subject->update($data);

        return $subject;
    }

    public function delete($id)
    {
        $subject = $this->find($id);
        $subject->delete();
    }
}
