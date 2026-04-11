<?php

namespace App\Repositories\Neo4j;

use App\Exceptions\DuplicateFieldException;
use App\Exceptions\Neo4jConstraintException;
use App\Exceptions\RecordNotFoundException;
use App\Exceptions\RegisteredStudentException;
use App\Exceptions\StudentEnrolledException;
use App\Interfaces\StudentRepositoryInterface;
use App\Traits\HasMeta;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Laudis\Neo4j\Types\Node;

class StudentNeo4jRepository implements StudentRepositoryInterface
{
    use HasMeta;

    public function __construct(
        protected ClientInterface          $client,
        protected SchoolNeo4jRepository   $schoolRepo,
        protected SubjectNeo4jRepository  $subjectRepo
    ) {}

    // ✅ Consistent with SchoolNeo4jRepository — public static, one method
    public static function nodeToArray(Node $node): array
    {
        return [
            'id'         => $node->getProperty('id'),
            'name'       => $node->getProperty('name'),
            'email'      => $node->getProperty('email'),
            'phone'      => $node->getProperty('phone'),
            'address'    => $node->getProperty('address'),
            'age'        => $node->getProperty('age'),
            'gender'     => $node->getProperty('gender'),
            'created_at' => $node->getProperty('created_at'),
            'updated_at' => $node->getProperty('updated_at'),
        ];
    }

    // Shapes a full student row (student + school + subjects)
    private static function toArray(array $row): array
    {
        /** @var Node $st */
        $st = $row['student'];
        /** @var Node|null $sc */
        $sc = $row['school'];
        /** @var iterable $subjects */
        $subjects = $row['subjects'];

        return [
            'student'  => self::nodeToArray($st),
            'school'   => $sc instanceof Node
                ? SchoolNeo4jRepository::nodeToArray($sc) // ✅ reuse School mapper
                : null,
            'subjects' => collect($subjects)
                ->filter()
                ->map(fn(Node $su) => SubjectNeo4jRepository::nodeToArray($su)) // ✅ reuse Subject mapper
                ->toArray(),
        ];
    }

    public function all(array $data): array
    {
        $page  = max(1, (int) ($data['page']  ?? 1));
        $limit = max(1, (int) ($data['limit'] ?? 10));
        $skip  = ($page - 1) * $limit;

        $result = $this->client->run(
            'MATCH (st:Student)
             WITH st
             ORDER BY coalesce(st.name, "") ASC
             SKIP $skip LIMIT $limit
             OPTIONAL MATCH (st)-[:BELONGS_TO]->(sc:School)
             OPTIONAL MATCH (st)-[:TAKES]->(su:Subject)
             WITH st, sc, collect(su) AS subjects
             RETURN st, sc, subjects',
            ['skip' => $skip, 'limit' => $limit]
        );

        $total = $this->client
            ->run('MATCH (st:Student) RETURN count(st) AS total')
            ->first()
            ->get('total');

        return [
            'data' => $result
                ->map(fn($row) => self::toArray([
                    'student'  => $row->get('st'),
                    'school'   => $row->get('sc'),
                    'subjects' => $row->get('subjects'),
                ]))
                ->toArray(),
            'meta' => self::buildMeta($page, $limit, $total),
        ];
    }

    public function find($id): array
    {
        $result = $this->client->run(
            'MATCH (st:Student {id: $id})
             OPTIONAL MATCH (st)-[:BELONGS_TO]->(sc:School)
             OPTIONAL MATCH (st)-[:TAKES]->(su:Subject)
             RETURN st, sc, collect(su) AS subjects LIMIT 1',
            ['id' => $id]
        );

        if ($result->isEmpty()) {
            throw new RecordNotFoundException("Student with id $id not found");
        }

        $row = $result->first();

        return self::toArray([
            'student'  => $row->get('st'),
            'school'   => $row->get('sc'),
            'subjects' => $row->get('subjects'),
        ]);
    }

    public function create(array $data): array
    {
        try {
            return $this->client->writeTransaction(
                static function (TransactionInterface $tsx) use ($data) {
                    $result = $tsx->run(
                        'MATCH (sc:School {id: $schoolId})
                         CREATE (st:Student {
                             id:         randomUUID(),
                             name:       $name,
                             email:      $email,
                             phone:      $phone,
                             address:    $address,
                             age:        $age,
                             gender:     $gender,
                             created_at: datetime(),
                             updated_at: datetime()
                         })
                         CREATE (st)-[:BELONGS_TO]->(sc)
                         WITH st, sc
                         RETURN st, sc',
                        [
                            'schoolId' => $data['school_id'],
                            'name'     => $data['name'],
                            'email'    => $data['email'],
                            'phone'    => $data['phone'],
                            'address'  => $data['address'] ?? null,
                            'age'      => (int) $data['age'],
                            'gender'   => $data['gender'],
                        ]
                    );

                    // ✅ Empty means school not found — transaction rolls back
                    if ($result->isEmpty()) {
                        throw new RecordNotFoundException(
                            "School with ID {$data['school_id']} not found."
                        );
                    }

                    // ✅ Register subjects in same transaction if provided
                    if (!empty($data['subject_ids'])) {
                        $tsx->run(
                            'MATCH (st:Student {id: $studentId})
                             UNWIND $subjectIds AS subjectId
                             MATCH (su:Subject {id: subjectId})
                             MERGE (st)-[:TAKES]->(su)',
                            [
                                'studentId'  => $result->first()->get('st')->getProperty('id'),
                                'subjectIds' => $data['subject_ids'],
                            ]
                        );
                    }

                    $record = $result->first();

                    return [
                        'student'  => self::nodeToArray($record->get('st')),
                        'school'   => SchoolNeo4jRepository::nodeToArray($record->get('sc')),
                        'subjects' => [],
                    ];
                }
            );
        } catch (\Laudis\Neo4j\Exception\Neo4jException $e) {
            // ✅ Consistent with SchoolNeo4jRepository — no pre-checks, let constraints handle it
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
                        'MATCH (st:Student {id: $id})
                         SET st += $props,
                             st.updated_at = datetime()
                         RETURN st',
                        [
                            'id'    => $id,
                            'props' => array_filter([
                                'name'    => $data['name']    ?? null,
                                'email'   => $data['email']   ?? null,
                                'phone'   => $data['phone']   ?? null,
                                'address' => $data['address'] ?? null,
                                'age'     => isset($data['age']) ? (int) $data['age'] : null,
                                'gender'  => $data['gender']  ?? null,
                            ], fn($v) => $v !== null),
                        ]
                    );

                    if ($result->isEmpty()) {
                        throw new RecordNotFoundException("Student with id $id not found");
                    }

                    return self::nodeToArray($result->first()->get('st'));
                }
            );
        } catch (\Laudis\Neo4j\Exception\Neo4jException $e) {
            // ✅ Consistent with SchoolNeo4jRepository::update()
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
        // ✅ Consistent with SchoolNeo4jRepository::delete() — no find() pre-check
        $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($id) {
                $result = $tsx->run(
                    'MATCH (st:Student {id: $id})
                     WITH st, count(st) AS found
                     DETACH DELETE st
                     RETURN found',
                    ['id' => $id]
                );

                if ($result->first()->get('found') === 0) {
                    throw new RecordNotFoundException("Student with id $id not found");
                }
            }
        );
    }

    public function enrollInSchool($studentId, $schoolId): array
    {
        return $this->client->writeTransaction(
            function (TransactionInterface $tsx) use ($studentId, $schoolId) {
                $existing = $tsx->run(
                    'MATCH (st:Student {id: $studentId})-[:BELONGS_TO]->(sc:School {id: $schoolId})
                     RETURN st LIMIT 1',
                    ['studentId' => $studentId, 'schoolId' => $schoolId]
                );

                if (!$existing->isEmpty()) {
                    throw new StudentEnrolledException('Student already enrolled.');
                }

                $result = $tsx->run(
                    'MATCH (st:Student {id: $studentId})
                     MATCH (sc:School  {id: $schoolId})
                     MERGE (st)-[:BELONGS_TO]->(sc)
                     RETURN st, sc',
                    ['studentId' => $studentId, 'schoolId' => $schoolId]
                );

                if ($result->isEmpty()) {
                    throw new RecordNotFoundException('Student or School not found.');
                }

                $record = $result->first();

                return [
                    'student' => self::nodeToArray($record->get('st')),
                    'school'  => SchoolNeo4jRepository::nodeToArray($record->get('sc')),
                ];
            }
        );
    }

    public function registerSubject($studentId, array $subjectIds = []): array
    {
        if (empty($subjectIds)) {
            throw new \InvalidArgumentException('No subjects provided.');
        }

        $existing = $this->client->run(
            'MATCH (st:Student {id: $studentId})-[:TAKES]->(su:Subject)
             RETURN su.id AS subjectId',
            ['studentId' => $studentId]
        );

        $alreadyRegistered = $existing
            ->map(fn($row) => $row->get('subjectId'))
            ->toArray();

        $newSubjects = array_values(array_diff($subjectIds, $alreadyRegistered));

        if (empty($newSubjects)) {
            throw new RegisteredStudentException('Student is already registered in all provided subjects.');
        }

        return $this->client->writeTransaction(
            function (TransactionInterface $tsx) use ($studentId, $newSubjects) {
                $result = $tsx->run(
                    'MATCH (st:Student {id: $studentId})
                     UNWIND $subjectIds AS subjectId
                     MATCH (su:Subject {id: subjectId})
                     MERGE (st)-[:TAKES]->(su)
                     RETURN st, collect(su) AS subjects, count(su) AS total_registered',
                    ['studentId' => $studentId, 'subjectIds' => $newSubjects]
                );

                if ($result->isEmpty()) {
                    throw new RecordNotFoundException('Student or Subjects not found.');
                }

                $record = $result->first();

                return [
                    'total_registered' => $record->get('total_registered'),
                    'student'          => self::nodeToArray($record->get('st')),
                    'subjects'         => collect($record->get('subjects'))
                        ->map(fn(Node $s) => SubjectNeo4jRepository::nodeToArray($s))
                        ->toArray(),
                ];
            }
        );
    }

    public function report(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $skip   = ($page - 1) * $limit;
        $params = ['skip' => (int) $skip, 'limit' => (int) $limit];
        $where  = [];

        $schoolMatch = !empty($filters['school_id'])
            ? 'MATCH (st)-[:BELONGS_TO]->(sc:School)'
            : 'OPTIONAL MATCH (st)-[:BELONGS_TO]->(sc:School)';

        if (!empty($filters['student_id'])) {
            $where[]              = 'st.id = $student_id';
            $params['student_id'] = $filters['student_id'];
        }

        if (!empty($filters['school_id'])) {
            $where[]             = 'sc.id = $school_id';
            $params['school_id'] = $filters['school_id'];
        }

        if (!empty($filters['from'])) {
            $where[]        = 'st.created_at >= datetime($from)';
            $params['from'] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $where[]      = 'st.created_at <= datetime($to)';
            $params['to'] = $filters['to'];
        }

        $whereClause = count($where)
            ? 'WHERE ' . implode(' AND ', $where)
            : '';

        $query = "
            MATCH (st:Student)
            $schoolMatch
            OPTIONAL MATCH (st)-[:TAKES]->(su:Subject)
            $whereClause
            WITH st, sc, collect(su) AS subjects
            ORDER BY st.name ASC
            SKIP \$skip LIMIT \$limit
            RETURN st AS student, sc AS school, subjects
        ";

        $result = $this->client->run($query, $params);

        $countQuery = "
            MATCH (st:Student)
            $schoolMatch
            $whereClause
            RETURN count(st) AS total
        ";

        $countParams = array_diff_key($params, array_flip(['skip', 'limit']));

        $total = $this->client
            ->run($countQuery, $countParams)
            ->first()
            ->get('total');

        return [
            'data' => $result
                ->map(fn($row) => self::toArray([
                    'student'  => $row->get('student'),
                    'school'   => $row->get('school'),
                    'subjects' => $row->get('subjects'),
                ]))
                ->toArray(),
            'meta' => self::buildMeta($page, $limit, $total),
        ];
    }

    public function unEnrollFromSchool($studentId): void
    {
        $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($studentId) {
                $tsx->run(
                    'MATCH (st:Student {id: $studentId})-[r:BELONGS_TO]->(:School)
                     DELETE r',
                    ['studentId' => $studentId]
                );
            }
        );
    }

    /** @used-by \App\Services\ValidationService */
    public function existsByEmail(string $email, ?string $exceptId = null): bool
    {
        $cypher = $exceptId
            ? 'MATCH (st:Student {email: $email}) WHERE st.id <> $id RETURN st LIMIT 1'
            : 'MATCH (st:Student {email: $email}) RETURN st LIMIT 1';

        // ✅ Consistent with SchoolNeo4jRepository — no null params passed
        $params = ['email' => $email];
        if ($exceptId) $params['id'] = $exceptId;

        return !$this->client->run($cypher, $params)->isEmpty();
    }

    /** @used-by \App\Services\ValidationService */
    public function existsByPhone(string $phone, ?string $exceptId = null): bool
    {
        $cypher = $exceptId
            ? 'MATCH (st:Student {phone: $phone}) WHERE st.id <> $id RETURN st LIMIT 1'
            : 'MATCH (st:Student {phone: $phone}) RETURN st LIMIT 1';

        $params = ['phone' => $phone];
        if ($exceptId) $params['id'] = $exceptId;

        return !$this->client->run($cypher, $params)->isEmpty();
    }
}
