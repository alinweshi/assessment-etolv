<?php

namespace App\Repositories\Neo4j;

use App\Exceptions\DuplicateFieldException;
use App\Exceptions\Neo4jConstraintException;
use App\Exceptions\RecordNotFoundException;
use App\Interfaces\SchoolRepositoryInterface;
use App\Traits\HasMeta;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Laudis\Neo4j\Types\Node;

class SchoolNeo4jRepository implements SchoolRepositoryInterface
{
    use HasMeta;

    public function __construct(protected ClientInterface $client) {}

    public static function nodeToArray(Node $node): array
    {
        $props = $node->getProperties();

        return [
            'id'      => $props->get('id'),
            'name'    => $props->get('name'),
            'address' => $props->get('address') ?? null,
            'phone'   => $props->get('phone') ?? null,
            'email'   => $props->get('email') ?? null,
            'website' => $props->get('website') ?? null,
            'created_at' => $props->get('created_at'),
            'updated_at' => $props->get('updated_at'),
            'deleted_at' => null

        ];
    }



    public function all(array $data): array
    {
        $page  = max(1, (int) ($data['page']  ?? 1));
        $limit = max(1, (int) ($data['limit'] ?? 10));
        $skip  = ($page - 1) * $limit;

        $result = $this->client->run(
            'MATCH (sc:School)
                WHERE sc.deleted_at IS NULL
                WITH sc
                ORDER BY sc.name ASC
                SKIP $skip LIMIT $limit
                RETURN sc',
            ['skip' => $skip, 'limit' => $limit]
        );

        $total = $this->client
            ->run('MATCH (sc:School) WHERE sc.deleted_at IS NULL RETURN count(sc) AS total')
            ->first()
            ->get('total');

        return [
            'data' => $result
                ->map(fn($row) => self::nodeToArray($row->get('sc')))
                ->toArray(),
            'meta' => self::buildMeta($page, $limit, $total),
        ];
    }

    public function find($id): array
    {
        $result = $this->client->run(
            'MATCH (sc:School {id: $id}) RETURN sc LIMIT 1',
            ['id' => $id]
        );

        if ($result->isEmpty()) {
            throw new RecordNotFoundException("School with id $id not found");
        }

        return self::nodeToArray($result->first()->get('sc'));
    }

    public function create(array $data): array
    {
        try {
            return $this->client->writeTransaction(
                static function (TransactionInterface $tsx) use ($data) {
                    $result = $tsx->run(
                        'CREATE (sc:School {
                            id:      randomUUID(),
                            name:    $name,
                            address: $address,
                            phone:   $phone,
                            email:   $email,
                            website: $website,
                            created_at: datetime(),
                            updated_at: datetime()
                        }) RETURN sc',
                        [
                            'name'    => $data['name'],
                            'address' => $data['address'] ?? null,
                            'phone'   => $data['phone']   ?? null,
                            'email'   => $data['email']   ?? null,
                            'website' => $data['website'] ?? null,
                        ]
                    );

                    if ($result->isEmpty()) {
                        throw new \Exception('Neo4j did not return a result.');
                    }

                    return self::nodeToArray($result->first()->get('sc'));
                }
            );
        } catch (\Laudis\Neo4j\Exception\Neo4jException $e) {
            if (Neo4jConstraintException::isConstraintViolation($e)) {
                $field = Neo4jConstraintException::parseField($e, [
                    'name'  => 'Name',
                    'email' => 'Email',
                    'phone' => 'Phone',
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
                        'MATCH (sc:School {id: $id})
                        WHERE sc.deleted_at IS NULL
                        SET sc += $props,
                            sc.updated_at = datetime()
                        RETURN sc',
                        [
                            'id'    => $id,
                            'props' => array_filter([
                                'name'    => $data['name']    ?? null,
                                'address' => $data['address'] ?? null,
                                'phone'   => $data['phone']   ?? null,
                                'email'   => $data['email']   ?? null,
                                'website' => $data['website'] ?? null,
                            ], fn($v) => $v !== null),
                        ]
                    );

                    if ($result->isEmpty()) {
                        throw new RecordNotFoundException("School with id $id not found");
                    }

                    return self::nodeToArray($result->first()->get('sc'));
                }
            );
        } catch (\Laudis\Neo4j\Exception\Neo4jException $e) {
            if (Neo4jConstraintException::isConstraintViolation($e)) {
                $field = Neo4jConstraintException::parseField($e, [
                    'name'  => 'Name',
                    'email' => 'Email',
                    'phone' => 'Phone',
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
                    'MATCH (sc:School {id: $id})
                 WHERE sc.deleted_at IS NULL
                 SET sc.deleted_at = datetime()
                 RETURN sc',
                    ['id' => $id]
                );

                if ($result->isEmpty()) {
                    throw new RecordNotFoundException("School with id $id not found");
                }
            }
        );
    }


    /** @used-by \App\Services\ValidationService */
    public function existsByEmail(string $email, ?string $exceptId = null): bool
    {
        $cypher = $exceptId
            ? 'MATCH (sc:School {email: $email}) WHERE sc.id <> $id RETURN sc LIMIT 1'
            : 'MATCH (sc:School {email: $email}) RETURN sc LIMIT 1';

        $params = ['email' => $email];
        if ($exceptId) $params['id'] = $exceptId;

        return !$this->client->run($cypher, $params)->isEmpty();
    }

    /** @used-by \App\Services\ValidationService */
    public function existsByPhone(string $phone, ?string $exceptId = null): bool
    {
        $cypher = $exceptId
            ? 'MATCH (sc:School {phone: $phone}) WHERE sc.id <> $id RETURN sc LIMIT 1'
            : 'MATCH (sc:School {phone: $phone}) RETURN sc LIMIT 1';

        $params = ['phone' => $phone];
        if ($exceptId) $params['id'] = $exceptId;

        return !$this->client->run($cypher, $params)->isEmpty();
    }
}
