<?php

namespace App\Repositories\Neo4j;

use App\Interfaces\SubjectRepositoryInterface;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;

class SubjectNeo4jRepository implements SubjectRepositoryInterface
{

    public function __construct(protected ClientInterface $client) {}

    public function all() {}

    public function find($id) {}

    public function create(array $data) {}

    public function update($id, array $data) {}

    public function delete($id) {}

    private static function toArray($node): array {}
}
