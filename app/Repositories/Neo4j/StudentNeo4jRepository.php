<?php

namespace App\Repositories\Neo4j;

use App\Interfaces\StudentRepositoryInterface;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;

class StudentNeo4jRepository implements StudentRepositoryInterface
{
    public function __construct(protected ClientInterface $client) {}

    public function all() {}

    public function find($id) {}

    public function create(array $data) {}

    public function update($id, array $data) {}

    public function delete($id) {}

    private static function toArray($node): array {}
    public function enrollInSchool($studentId, $schoolId) {}

    public function registerSubject($studentId, $subjectId) {}

    public function report() {}
}
