<?php

namespace App\Repositories\Neo4j;

use App\Exceptions\DuplicateFieldException;
use App\Exceptions\Neo4jConstraintException;
use App\Exceptions\RecordNotFoundException;
use App\Interfaces\SubjectRepositoryInterface;
use App\Traits\HasMeta;
use App\Traits\MapsNode;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;

class SubjectNeo4jRepository implements SubjectRepositoryInterface
{
    use HasMeta, MapsNode;

    public function __construct(protected ClientInterface $client) {}


    protected static function fields(): array
    {
        return ['id', 'name', 'created_at', 'updated_at'];
    }

    public function all(array $data): array
    {
        $page   = max(1, (int) ($data['page']  ?? 1));
        $limit  = max(1, (int) ($data['limit'] ?? 10));
        $skip   = ($page - 1) * $limit;
        $params = ['skip' => $skip, 'limit' => $limit];
        $where  = [];

        // ─── Optional filters ─────────────────────────────────────
        if (!empty($data['search'])) {
            $where[]          = 'su.name CONTAINS $search';
            $params['search'] = $data['search'];
        }

        $whereClause = count($where)
            ? 'WHERE ' . implode(' AND ', $where)
            : '';

        // ─── Data query ───────────────────────────────────────────
        $result = $this->client->run(
            "MATCH (su:Subject)
         $whereClause
         WITH su
         ORDER BY su.name ASC
         SKIP \$skip LIMIT \$limit
         RETURN su",
            $params
        );
        // dd($result
        //     ->map(fn($row) => self::nodeToArray($row->get('su')))
        //     ->toArray()); // Debugging line to inspect the raw result from Neo4j

        // ─── Count query — mirrors same filters ───────────────────
        $countParams = array_diff_key($params, array_flip(['skip', 'limit']));

        $total = $this->client
            ->run(
                "MATCH (su:Subject)
             $whereClause
             RETURN count(su) AS total",
                $countParams
            )
            ->first()
            ->get('total');

        return [
            'data' => $result
                ->map(fn($row) => self::nodeToArray($row->get('su')))
                ->toArray(),
            'meta' => self::buildMeta($page, $limit, $total),
        ];
    }

    public function find($id): array
    {
        $result = $this->client->run(
            'MATCH (su:Subject {id: $id}) RETURN su LIMIT 1',
            ['id' => $id]
        );

        if ($result->isEmpty()) {
            throw new RecordNotFoundException("Subject with id $id not found");
        }

        return self::nodeToArray($result->first()->get('su'));
    }

    public function create(array $data): array
    {
        try {
            return $this->client->writeTransaction(
                static function (TransactionInterface $tsx) use ($data) {
                    $result = $tsx->run(
                        'CREATE (su:Subject {
                            id:   randomUUID(),
                            name: $name,
                            created_at: datetime(),
                            updated_at: datetime()
                        }) RETURN su',
                        ['name' => $data['name']]
                    );

                    if ($result->isEmpty()) {
                        throw new \Exception('Neo4j did not return a result.');
                    }

                    return self::nodeToArray($result->first()->get('su'));
                }
            );
        } catch (\Laudis\Neo4j\Exception\Neo4jException $e) {
            if (Neo4jConstraintException::isConstraintViolation($e)) {
                $field = Neo4jConstraintException::parseField($e, [
                    'name' => 'Name',
                ]);
                throw new DuplicateFieldException("{$field} already exists.");
            }
            throw $e;
        }
    }

    public function update($id, array $data): array
    {
        try {
            return $this->client->writeTransaction(
                static function (TransactionInterface $tsx) use ($id, $data) {
                    $result = $tsx->run(
                        'MATCH (su:Subject {id: $id})
                         SET su += $props
                         RETURN su',
                        [
                            'id'    => $id,
                            'props' => array_filter([
                                'name' => $data['name'] ?? null,
                            ], fn($v) => $v !== null),
                        ]
                    );

                    if ($result->isEmpty()) {
                        throw new RecordNotFoundException("Subject with id $id not found");
                    }

                    return self::nodeToArray($result->first()->get('su'));
                }
            );
        } catch (\Laudis\Neo4j\Exception\Neo4jException $e) {
            if (Neo4jConstraintException::isConstraintViolation($e)) {
                $field = Neo4jConstraintException::parseField($e, [
                    'name' => 'Name',
                ]);
                throw new DuplicateFieldException("{$field} already exists.");
            }
            throw $e;
        }
    }

    public function delete($id): void
    {
        $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($id) {
                $result = $tsx->run(
                    'MATCH (su:Subject {id: $id})
                     WITH su, count(su) AS found
                     DETACH DELETE su
                     RETURN found',
                    ['id' => $id]
                );

                if ($result->first()->get('found') === 0) {
                    throw new RecordNotFoundException("Subject with id $id not found");
                }
            }
        );
    }
}
