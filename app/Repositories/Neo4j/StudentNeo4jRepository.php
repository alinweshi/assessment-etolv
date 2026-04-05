<?php

namespace App\Repositories\Neo4j;

use App\Exceptions\RecordNotFoundException;
use App\Interfaces\StudentRepositoryInterface;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;

class StudentNeo4jRepository implements StudentRepositoryInterface
{
    public function __construct(
        protected ClientInterface $client,
        protected SchoolNeo4jRepository $schoolRepo,
        protected SubjectNeo4jRepository $subjectRepo
    ) {}
    public function all()
    {
        $skip = (request()->input('page', 1) - 1) * 10;

        return $this->client->run(
            'MATCH (st:Student)
             RETURN st
             ORDER BY st.name
             SKIP $skip LIMIT 10',
            ['skip' => $skip]
        )->map(fn($row) => $this->toArray($row->get('st')))->toArray();
    }

    public function find($id)
    {
        $result = $this->client->run(
            'MATCH (st:Student {id: $id}) RETURN st LIMIT 1',
            ['id' => $id]
        );

        if ($result->isEmpty()) {
            throw new RecordNotFoundException("Student with id $id not found");
        }

        return $this->toArray($result->first()->get('st'));
    }

    public function create(array $data)
    {
        return $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($data) {

                $result = $tsx->run(
                    'CREATE (st:Student {
                        id: randomUUID(),
                        name: $name,
                        email: $email,
                        phone: $phone,
                        address: $address,
                        age: $age,
                        gender: $gender
                    }) RETURN st',
                    [
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'],
                        'address' => $data['address'] ?? null,
                        'age' => $data['age'],
                        'gender' => $data['gender'],
                    ]
                );

                if ($result->isEmpty()) {
                    throw new \Exception('Neo4j did not return a result');
                }

                return self::toArray($result->first()->get('st'));
            }
        );
    }

    public function update($id, array $data)
    {
        // ensure exists
        $this->find($id);

        return $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($id, $data) {

                $result = $tsx->run(
                    'MATCH (st:Student {id: $id})
                     SET st.name = $name,
                         st.email = $email,
                         st.phone = $phone,
                         st.address = $address,
                         st.age = $age,
                         st.gender = $gender
                     RETURN st',
                    [
                        'id' => $id,
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'],
                        'address' => $data['address'] ?? null,
                        'age' => $data['age'],
                        'gender' => $data['gender'],
                    ]
                );

                if ($result->isEmpty()) {
                    throw new RecordNotFoundException("Student with id $id not found");
                }

                return self::toArray($result->first()->get('st'));
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

    private static function toArray($node): array
    {
        return [
            'id' => $node->getProperty('id'),
            'name' => $node->getProperty('name') ?? null,
            'email' => $node->getProperty('email') ?? null,
            'phone' => $node->getProperty('phone') ?? null,
            'address' => $node->getProperty('address') ?? null,
            'age' => $node->getProperty('age') ?? null,
            'gender' => $node->getProperty('gender') ?? null,
        ];
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
            throw new \Exception('Student is already enrolled in this school.');
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
            'student' => $this->toArray($record->get('st')),
            'school'  => $this->schoolRepo->toArray($record->get('sc')),
        ];
    }

    // 🔗 Student -> Subject
    public function registerSubject($studentId, array $subjectIds = [])
    {
        if (empty($subjectIds)) {
            return false;
        }

        return $this->client->writeTransaction(
            function (TransactionInterface $tsx) use ($studentId, $subjectIds) {

                $result = $tsx->run(
                    '
                MATCH (st:Student {id: $studentId})
                UNWIND $subjectIds AS subjectId
                MATCH (su:Subject {id: subjectId})
                MERGE (st)-[:TAKES]->(su)
                RETURN st, collect(su) as subjects, count(su) AS total_registered
                ',
                    [
                        'studentId' => $studentId,
                        'subjectIds' => $subjectIds
                    ]
                );

                if ($result->isEmpty()) {
                    throw new RecordNotFoundException("Student or Subjects not found");
                }

                $record = $result->first();

                return [
                    'total_registered' => $record->get('total_registered'),
                    'student' => $this->toArray($record->get('st')),
                    'subjects' => $record->get('subjects')
                        ? collect($record->get('subjects'))
                        ->map(fn($s) => app(SubjectNeo4jRepository::class)->toArray($s))
                        ->toArray()
                        : [],
                ];
            }
        );
    }
    // 📊 Report
    public function report()
    {
        $result = $this->client->run(
            'MATCH (st:Student)
             OPTIONAL MATCH (st)-[:ENROLLED_IN]->(sc:School)
             OPTIONAL MATCH (st)-[:TAKES]->(su:Subject)
             RETURN st, sc, collect(su) as subjects'
        );

        return $result->map(function ($row) {

            $student = $row->get('st');
            $school = $row->get('sc');
            $subjects = $row->get('subjects');

            return [
                'id' => $student->getProperty('id'),
                'name' => $student->getProperty('name'),
                'email' => $student->getProperty('email'),
                'phone' => $student->getProperty('phone'),
                'school' => $school?->getProperty('name'),
                'subjects' => collect($subjects)
                    ->map(fn($s) => $s->getProperty('name'))
                    ->toArray(),
            ];
        })->toArray();
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
