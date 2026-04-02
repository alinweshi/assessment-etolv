<?php

namespace App\Repositories\Neo4j;

use App\Exceptions\RecordNotFoundException;
use App\Interfaces\SubjectRepositoryInterface;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;

class SubjectNeo4jRepository implements SubjectRepositoryInterface
{
    public function __construct(protected ClientInterface $client) {}

    public function all()
    {
        $skip = (request()->input('page', 1) - 1) * 10;

        return $this->client->run(
            'MATCH (su:Subject)
             RETURN su
             ORDER BY su.name
             SKIP $skip LIMIT 10',
            ['skip' => $skip]
        )->map(fn($row) => $this->toArray($row->get('su')))->toArray();
    }

    public function find($id)
    {
        $result = $this->client->run(
            'MATCH (su:Subject {id: $id}) RETURN su LIMIT 1',
            ['id' => $id]
        );

        if ($result->isEmpty()) {
            throw new RecordNotFoundException("Subject with id $id not found");
        }

        $record = $result->first();

        return $this->toArray($record->get('su'));
    }

    public function create(array $data)
    {
        return $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($data) {

                $result = $tsx->run(
                    'CREATE (su:Subject {
                        id: randomUUID(),
                        name: $name
                    }) RETURN su',
                    [
                        'name' => $data['name'],
                    ]
                );

                if ($result->isEmpty()) {
                    throw new \Exception('Neo4j did not return a result');
                }

                $record = $result->first();

                return self::toArray($record->get('su'));
            }
        );
    }

    public function update($id, array $data)
    {
        // Ensure exists (reuses your safe find)
        $this->find($id);

        return $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($id, $data) {

                $result = $tsx->run(
                    'MATCH (su:Subject {id: $id})
                     SET su.name = $name
                     RETURN su',
                    [
                        'id' => $id,
                        'name' => $data['name']
                    ]
                );

                if ($result->isEmpty()) {
                    throw new RecordNotFoundException("Subject with id $id not found");
                }

                return self::toArray($result->first()->get('su'));
            }
        );
    }

    public function delete($id)
    {
        // Ensure exists
        $this->find($id);

        $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($id) {
                $tsx->run(
                    'MATCH (su:Subject {id: $id})
                     DETACH DELETE su',
                    ['id' => $id]
                );
            }
        );
    }

    public static function toArray($node): array
    {
        return [
            'id' => $node->getProperty('id'),
            'name' => $node->getProperty('name') ?? null,
        ];
    }
}
