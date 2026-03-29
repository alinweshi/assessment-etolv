<?php

namespace App\Repositories\Neo4j;

use App\Interfaces\SchoolRepositoryInterface;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;

class SchoolNeo4jRepository implements SchoolRepositoryInterface
{
    public function __construct(protected ClientInterface $client) {}

    public function all()
    {
        $skip = (request()->get('page', 1) - 1) * 10;

        return $this->client->run(
            'MATCH (sc:School)
             RETURN sc
             ORDER BY sc.name
             SKIP $skip LIMIT 10',
            ['skip' => $skip]
        )->map(fn($row) => $this->toArray($row->get('sc')))->toArray();
    }

    public function find($id)
    {
        $result = $this->client->run(
            'MATCH (sc:School {id: $id}) RETURN sc',
            ['id' => $id]
        );
        return $this->toArray($result->first()->get('sc'));
    }

    public function create(array $data)
    {
        return $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($data) {
                $result = $tsx->run(
                    'CREATE (sc:School {id: randomUUID(), name: $name}) RETURN sc',
                    ['name' => $data['name']]
                );
                return self::toArray($result->first()->get('sc'));
            }
        );
    }

    public function update($id, array $data)
    {
        return $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($id, $data) {
                $result = $tsx->run(
                    'MATCH (sc:School {id: $id}) SET sc.name = $name RETURN sc',
                    ['id' => $id, 'name' => $data['name']]
                );
                return self::toArray($result->first()->get('sc'));
            }
        );
    }

    public function delete($id)
    {
        $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($id) {
                $tsx->run(
                    'MATCH (sc:School {id: $id}) DETACH DELETE sc',
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
