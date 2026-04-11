<?php

namespace App\Services;

use App\Interfaces\StudentRepositoryInterface;

class StudentService
{
    public function __construct(protected StudentRepositoryInterface $repo) {}

    public function all($request)
    {
        return $this->repo->all($request);
    }
    public function find($id)
    {
        return $this->repo->find($id);
    }
    public function create(array $data)
    {

        return $this->repo->create($data);
    }
    public function update($id, array $data)
    {
        // 1. Update basic info
        $student = $this->repo->update($id, $data);

        // 2. Update School (if provided)
        if (!empty($data['school_id'])) {
            $this->repo->enrollInSchool($id, $data['school_id']);
        }

        // 3. Update Subjects (if provided)
        if (!empty($data['subject_ids'])) {
            $this->repo->registerSubject($id, $data['subject_ids']);
        }

        // 4. Return the fresh, full data structure
        return $this->repo->find($id);
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
    public function report(int $page, int $limit = 20, array $filters = []): array
    {
        return $this->repo->report($page, $limit, $filters);
    }
}
