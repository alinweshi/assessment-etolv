<?php

namespace App\Repositories;

use App\Exceptions\RecordNotFoundException;
use App\Interfaces\SchoolRepositoryInterface;

class SchoolRepository implements SchoolRepositoryInterface
{
    /**
     * @param string $model  Eloquent or Neo4jEloquent model class
     */
    public function __construct(protected string $model) {}

    public function all()
    {
        return $this->model::paginate(10);
    }

    public function find($id)
    {
        $school = $this->model::find($id);

        if (!$school) {
            throw new RecordNotFoundException("School with id $id not found");
        }

        return $school;
    }

    public function create(array $data)
    {
        return $this->model::create($data);
    }

    public function update($id, array $data)
    {
        $school = $this->find($id); // reuses find() + throws if missing

        $school->update($data);

        return $school;
    }

    public function delete($id)
    {
        $school = $this->find($id);

        $school->delete();
    }
}
