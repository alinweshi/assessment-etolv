<?php

namespace App\Services;

use App\Interfaces\StudentRepositoryInterface;

class StudentService
{
    public function __construct(protected StudentRepositoryInterface $repo) {}

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
    public function enrollInSchool($sId, $scId)
    {
        return $this->repo->enrollInSchool($sId, $scId);
    }
    public function registerSubject($sId, $subId)
    {
        return $this->repo->registerSubject($sId, $subId);
    }
    public function report()
    {
        return $this->repo->report();
    }
}
