<?php

namespace App\Repositories\Neo4j;

use App\Exceptions\DuplicateFieldException;
use App\Exceptions\Neo4jConstraintException;
use App\Exceptions\RecordNotFoundException;
use App\Interfaces\SubjectRepositoryInterface;
use App\Traits\HasMeta;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Laudis\Neo4j\Types\Node;

class SubjectNeo4jRepository implements SubjectRepositoryInterface
{
    use HasMeta;

    public function __construct(protected ClientInterface $client) {}

    // ✅ Typed Node, consistent with School and Student
    public static function nodeToArray(Node $node): array
    {
        return [
            'id'   => $node->getProperty('id'),
            'name' => $node->getProperty('name'),
        ];
    }

    public function all(array $data): array
    {
        $page  = max(1, (int) ($data['page']  ?? 1));
        $limit = max(1, (int) ($data['limit'] ?? 10));
        $skip  = ($page - 1) * $limit;

        // ✅ No more request() helper in the repository
        $result = $this->client->run(
            'MATCH (su:Subject)
             WITH su
             ORDER BY su.name ASC
             SKIP $skip LIMIT $limit
             RETURN su',
            ['skip' => $skip, 'limit' => $limit]
        );

        $total = $this->client
            ->run('MATCH (su:Subject) RETURN count(su) AS total')
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
            // ✅ Constraint-based duplicate detection — no pre-checks needed
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
                    // ✅ No find() pre-check — let the transaction handle not-found
                    $result = $tsx->run(
                        'MATCH (su:Subject {id: $id})
                         SET su += $props
                         RETURN su',
                        [
                            'id'    => $id,
                            // ✅ SET su += $props instead of hardcoded SET su.name = $name
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
        // ✅ No find() pre-check — single transaction, count check inside
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
