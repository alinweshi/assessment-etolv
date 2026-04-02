<?php

namespace App\Repositories\Neo4j;

use App\Exceptions\RecordNotFoundException;
use App\Interfaces\SchoolRepositoryInterface;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;


class SchoolNeo4jRepository implements SchoolRepositoryInterface
{
    public function __construct(protected ClientInterface $client) {}

    public function all()
    {
        $skip = (request()->input('page', 1) - 1) * 10;

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
            'MATCH (sc:School {id: $id}) RETURN sc LIMIT 1',
            ['id' => $id]
        );

        if ($result->isEmpty()) {
            throw new RecordNotFoundException("School with id $id not found");
        }

        $record = $result->first();

        return $this->toArray($record->get('sc'));
    }
    public function create(array $data)
    {
        return $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($data) {
                $result = $tsx->run(
                    'CREATE (sc:School {
                    id: randomUUID(),
                    name: $name,
                    address: $address,
                    phone: $phone,
                    email: $email,
                    website: $website
                }) RETURN sc',
                    [
                        'name' => $data['name'],
                        'address' => $data['address'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'email' => $data['email'] ?? null,
                        'website' => $data['website'] ?? null,
                    ]
                );

                $record = $result->first();

                if (!$record) {
                    throw new \Exception('Neo4j did not return a result');
                }

                $node = $record->get('sc');

                return self::toArray($node);
            }
        );
    }

    public function update($id, array $data)
    {
        $school = $this->find($id);

        if (!$school) {
            throw new RecordNotFoundException("School with id $id not found");
        }

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
        $school = $this->find($id);

        if (!$school) {
            throw new RecordNotFoundException("School with id $id not found");
        }
        $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($id) {
                $tsx->run(
                    'MATCH (sc:School {id: $id}) DETACH DELETE sc',
                    ['id' => $id]
                );
            }
        );
    }

    public function findByName(string $name)
    {
        $result = $this->client->run(
            'MATCH (s:School {name: $name}) RETURN s LIMIT 1',
            ['name' => $name]
        );
        if ($result->isEmpty()) {
            return [];
        }

        return $result->first()?->get('s');
    }

    private static function toArray($node): array
    {
        return [
            'id' => $node->getProperty('id'),
            'name' => $node->getProperty('name') ?? null,
            'address' => $node->getProperty('address') ?? null,
            'phone' => $node->getProperty('phone') ?? null,
            'email' => $node->getProperty('email') ?? null,
            'website' => $node->getProperty('website') ?? null,

        ];
    }
}
