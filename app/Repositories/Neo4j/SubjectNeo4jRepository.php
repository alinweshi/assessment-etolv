<?php

namespace App\Repositories\Neo4j;

use App\Interfaces\SubjectRepositoryInterface;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;

class SubjectNeo4jRepository implements SubjectRepositoryInterface
{
    public function __construct(protected ClientInterface $client) {}

    public function all()
    {
        $skip = (request()->get('page', 1) - 1) * 10;

        return $this->client->run(
            'MATCH (sub:Subject)
             RETURN sub
             ORDER BY sub.name
             SKIP $skip LIMIT 10',
            ['skip' => $skip]
        )->map(fn($row) => $this->toArray($row->get('sub')))->toArray();
    }

    public function find($id)
    {
        $result = $this->client->run(
            'MATCH (sub:Subject {id: $id}) RETURN sub',
            ['id' => $id]
        );
        return $this->toArray($result->first()->get('sub'));
    }

    public function create(array $data)
    {
        return $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($data) {
                $result = $tsx->run(
                    'CREATE (sub:Subject {id: randomUUID(), name: $name}) RETURN sub',
                    ['name' => $data['name']]
                );
                return self::toArray($result->first()->get('sub'));
            }
        );
    }

    public function update($id, array $data)
    {
        return $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($id, $data) {
                $result = $tsx->run(
                    'MATCH (sub:Subject {id: $id}) SET sub.name = $name RETURN sub',
                    ['id' => $id, 'name' => $data['name']]
                );
                return self::toArray($result->first()->get('sub'));
            }
        );
    }

    public function delete($id)
    {
        $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($id) {
                $tsx->run(
                    'MATCH (sub:Subject {id: $id}) DETACH DELETE sub',
                    ['id' => $id]
                );
            }
        );
    }

    private static function toArray($node): array
    {
        return [
            'id'   => $node->getProperty('id'),
            'name' => $node->getProperty('name'),
        ];
    }
}
