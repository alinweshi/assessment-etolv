<?php

namespace App\Services;

use App\Interfaces\SubjectRepositoryInterface;

class SubjectService
{
    public function __construct(protected SubjectRepositoryInterface $repo) {}

    public function all()
    {
        return $this->repo->all();
    }
    public function find($id)
    {
        return $this->repo->find($id);
    }
    public function create(array $data)
    {
        return $this->repo->create($data);
    }
    public function update($id, $data)
    {
        return $this->repo->update($id, $data);
    }
    public function delete($id)
    {
        return $this->repo->delete($id);
    }
}
