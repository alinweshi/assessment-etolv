<?php

namespace App\Repositories\Neo4j;

use App\Exceptions\RecordNotFoundException;
use App\Exceptions\RegisteredStudentException;
use App\Exceptions\StudentEnrolledException;
use App\Interfaces\StudentRepositoryInterface;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Laudis\Neo4j\Types\Node;

class StudentNeo4jRepository implements StudentRepositoryInterface
{
    public function __construct(
        protected ClientInterface $client,
        protected SchoolNeo4jRepository $schoolRepo,
        protected SubjectNeo4jRepository $subjectRepo
    ) {}
    // Converts a single Student Node → plain array
    private static function nodeToArray(Node $node): array
    {
        // Use getProperties() to get everything, or keep explicit mapping for control
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

    private static function toArray(array $row): array
    {
        /** @var Node $st */
        $st = $row['student'];
        /** @var Node|null $sc */
        $sc = $row['school'];
        /** @var iterable $subjects */
        $subjects = $row['subjects'];

        return [
            // 1. Reuse your helper here!
            'student' => self::nodeToArray($st),

            // 2. Clean null-safe check for School
            'school' => $sc instanceof Node ? [
                'id'   => $sc->getProperty('id'),
                'name' => $sc->getProperty('name'),
            ] : null,

            // 3. Map subjects efficiently
            'subjects' => collect($subjects)
                ->filter() // Removes nulls if OPTIONAL MATCH found nothing
                ->map(fn(Node $su) => [
                    'id'   => $su->getProperty('id'),
                    'name' => $su->getProperty('name'),
                ])
                ->toArray()
        ];
    }

    public function all(array $data): array
    {
        $page  = max(1, (int) ($data['page']  ?? 1));
        $limit = max(1, (int) ($data['limit'] ?? 10));
        $skip  = ($page - 1) * $limit;

        $result = $this->client->run(
            'MATCH (st:Student)
         // Clause 1: Order and Paginate the "Anchor" nodes first
         WITH st
         ORDER BY coalesce(st.name, "")
         SKIP $skip LIMIT $limit
         
         // Clause 2: Only expand relationships for the 10 students we actually need
         OPTIONAL MATCH (st)-[:BELONGS_TO]->(sc:School)
         OPTIONAL MATCH (st)-[:TAKES]->(su:Subject)
         
         // Clause 3: Group the intermediate table results
         WITH st, sc, collect(su) AS subjects
         
         // Clause 4: Final Output
         RETURN st, sc, subjects',
            ['skip' => $skip, 'limit' => $limit]
        );

        $students = collect($result)
            ->map(fn($row) => self::toArray([
                'student'  => $row->get('st'),
                'school'   => $row->get('sc'),
                'subjects' => $row->get('subjects'),
            ]));

        $total = $this->client
            ->run('MATCH (st:Student) RETURN count(st) AS total')
            ->first()
            ->get('total');

        return [
            'data' => $students,
            'meta' => [
                'page'      => $page,
                'limit'     => $limit,
                'total'     => $total,
                'last_page' => (int) ceil($total / $limit),
            ],
        ];
    }

    public function find($id)
    {
        // Fetching the student with their relationships even in 'find' 
        // makes the Resource much happier and provides a richer API response.
        $result = $this->client->run(
            'MATCH (st:Student {id: $id})
         OPTIONAL MATCH (st)-[:BELONGS_TO]->(sc:School)
         OPTIONAL MATCH (st)-[:TAKES]->(su:Subject)
         RETURN st, sc, collect(su) as subjects LIMIT 1',
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

    public function create(array $data)
    {
        return $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($data) {
                $result = $tsx->run(
                    'MATCH (sc:School {id: $schoolId})
                 CREATE (st:Student {
                    id: randomUUID(),
                    name: $name,
                    email: $email,
                    phone: $phone,
                    address: $address,
                    age: $age,
                    gender: $gender,
                    created_at: datetime(),
                    updated_at: datetime()
                 })
                 
                 RETURN st, sc', // Return both to satisfy the Resource structure
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

                if ($result->isEmpty()) {
                    throw new \App\Exceptions\RecordNotFoundException("School with ID {$data['school_id']} not found.");
                }

                $record = $result->first();

                // Return in the exact shape the StudentResource expects
                // In StudentNeo4jRepository.php
                $record = $result->first();

                return [
                    'student'  => self::nodeToArray($record->get('st')),
                    'school'   => [
                        'id'   => $record->get('sc')->getProperty('id'),
                        'name' => $record->get('sc')->getProperty('name'),
                    ],
                    'subjects' => []
                ];
            }
        );
    }
    // public function create(array $data)
    // {
    //     return $this->client->writeTransaction(
    //         static function (TransactionInterface $tsx) use ($data) {
    //             $result = $tsx->run(
    //                 'MATCH (sc:School {id: $schoolId})
    //              CREATE (st:Student {
    //                 id: randomUUID(),
    //                 name: $name,
    //                 email: $email,
    //                 phone: $phone,
    //                 address: $address,
    //                 age: $age,
    //                 gender: $gender,
    //                 created_at: datetime(),
    //                 updated_at: datetime()
    //              })
    //              CREATE (st)-[:BELONGS_TO]->(sc)
    //              RETURN st, sc', // Return both to satisfy the Resource structure
    //                 [
    //                     'schoolId' => $data['school_id'],
    //                     'name'     => $data['name'],
    //                     'email'    => $data['email'],
    //                     'phone'    => $data['phone'],
    //                     'address'  => $data['address'] ?? null,
    //                     'age'      => (int) $data['age'],
    //                     'gender'   => $data['gender'],
    //                 ]
    //             );

    //             if ($result->isEmpty()) {
    //                 throw new \App\Exceptions\RecordNotFoundException("School with ID {$data['school_id']} not found.");
    //             }

    //             $record = $result->first();

    //             // Return in the exact shape the StudentResource expects
    //             // In StudentNeo4jRepository.php
    //             $record = $result->first();

    //             return [
    //                 'student'  => self::nodeToArray($record->get('st')),
    //                 'school'   => [
    //                     'id'   => $record->get('sc')->getProperty('id'),
    //                     'name' => $record->get('sc')->getProperty('name'),
    //                 ],
    //                 'subjects' => []
    //             ];
    //         }
    //     );
    // }




    public function update($id, array $data)
    {
        return $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($id, $data) {
                // 1. Update Student Properties only
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
                            'age'     => isset($data['age']) ? (int)$data['age'] : null,
                            'gender'  => $data['gender']  ?? null,
                        ], fn($v) => $v !== null),
                    ]
                );

                if ($result->isEmpty()) {
                    throw new RecordNotFoundException("Student with id $id not found");
                }

                // Return the flat student; the Service will fetch the rest
                return self::nodeToArray($result->first()->get('st'));
            }
        );
    }
    public function delete($id)
    {
        $this->find($id);

        $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($id) {
                $tsx->run(
                    'MATCH (st:Student {id: $id})
                     DETACH DELETE st',
                    ['id' => $id]
                );
            }
        );
    }


    // 🔗 Student -> School
    public function enrollInSchool($studentId,  $schoolId): array
    {
        // Check already enrolled
        $existing = $this->client->run('
        MATCH (st:Student {id: $studentId})-[:BELONGS_TO]->(sc:School {id: $schoolId})
        RETURN st LIMIT 1
    ', ['studentId' => $studentId, 'schoolId' => $schoolId]);

        if (!$existing->isEmpty()) {
            throw new StudentEnrolledException('Student is already enrolled in this school.');
        }

        $result = $this->client->run('
        MATCH (st:Student {id: $studentId})
        MATCH (sc:School {id: $schoolId})
        MERGE (st)-[:BELONGS_TO]->(sc)
        RETURN st, sc
    ', ['studentId' => $studentId, 'schoolId' => $schoolId]);

        if ($result->isEmpty()) {
            throw new RecordNotFoundException('Student or School not found');
        }

        $record = $result->first();

        return [
            'student' => self::nodeToArray($record->get('st')),
            'school'  => SchoolNeo4jRepository::toArray($record->get('sc')), // ← static call, no app()
        ];
    }

    // 🔗 Student -> Subject
    public function registerSubject($studentId, array $subjectIds = []): array
    {
        if (empty($subjectIds)) {
            throw new \InvalidArgumentException('No subjects provided.');
        }

        // 1. Find already registered subjects
        $existing = $this->client->run('
        MATCH (st:Student {id: $studentId})-[:TAKES]->(su:Subject)
        RETURN su.id AS subjectId
    ', ['studentId' => $studentId]);

        $alreadyRegistered = collect($existing)
            ->map(fn($row) => $row->get('subjectId'))
            ->toArray();

        // 2. Only register subjects not already taken
        $newSubjects = array_values(array_diff($subjectIds, $alreadyRegistered));

        if (empty($newSubjects)) {
            throw new RegisteredStudentException('Student is already registered in all provided subjects.');
        }

        // 3. Register only new subjects
        return $this->client->writeTransaction(
            function (TransactionInterface $tsx) use ($studentId, $newSubjects) {

                $result = $tsx->run('
                MATCH (st:Student {id: $studentId})
                UNWIND $subjectIds AS subjectId
                MATCH (su:Subject {id: subjectId})
                MERGE (st)-[:TAKES]->(su)
                RETURN st, collect(su) AS subjects, count(su) AS total_registered
            ', [
                    'studentId'  => $studentId,
                    'subjectIds' => $newSubjects,   // ← fixed
                ]);

                if ($result->isEmpty()) {
                    throw new RecordNotFoundException('Student or Subjects not found.');
                }

                $record = $result->first();

                return [
                    'total_registered' => $record->get('total_registered'),
                    'student'          => self::nodeToArray($record->get('st')),
                    'subjects'         => collect($record->get('subjects'))
                        ->map(fn($s) => SubjectNeo4jRepository::toArray($s))  // ← static call, no app()
                        ->toArray(),
                ];
            }
        );
    }
    // 📊 Report
    public function report()
    {
        $result = $this->client->run(
            'MATCH (st:Student)
         OPTIONAL MATCH (st)-[:BELONGS_TO]->(sc:School) // Changed from ENROLLED_IN
         OPTIONAL MATCH (st)-[:TAKES]->(su:Subject)
         RETURN st, sc, collect(su) as subjects'
        );

        return $result->map(fn($row) => self::toArray([
            'student'  => $row->get('st'),
            'school'   => $row->get('sc'),
            'subjects' => $row->get('subjects'),
        ]))->toArray();
    }
    public function existsByEmail(string $email, ?string $exceptId = null): bool
    {
        $cypher = $exceptId
            ? 'MATCH (s:Student {email: $email}) WHERE s.id <> $id RETURN s LIMIT 1'
            : 'MATCH (s:Student {email: $email}) RETURN s LIMIT 1';

        return !$this->client->run(
            $cypher,
            ['email' => $email, 'id' => $exceptId]
        )->isEmpty();
    }

    public function existsByPhone(string $phone, ?string $exceptId = null): bool
    {
        $cypher = $exceptId
            ? 'MATCH (s:Student {phone: $phone}) WHERE s.id <> $id RETURN s LIMIT 1'
            : 'MATCH (s:Student {phone: $phone}) RETURN s LIMIT 1';

        return !$this->client->run(
            $cypher,
            ['phone' => $phone, 'id' => $exceptId]
        )->isEmpty();
    }
}
