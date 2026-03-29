<?php

namespace App\Repositories\Neo4j;

use App\Interfaces\StudentRepositoryInterface;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;

class StudentNeo4jRepository implements StudentRepositoryInterface
{
    public function __construct(protected ClientInterface $client) {}

    public function all()
    {
        $skip = (request()->get('page', 1) - 1) * 10;

        return $this->client->run(
            'MATCH (s:Student)
             OPTIONAL MATCH (s)-[:ENROLLED_IN]->(sc:School)
             RETURN s, sc.name AS school
             ORDER BY s.name SKIP $skip LIMIT 10',
            ['skip' => $skip]
        )->map(fn($row) => array_merge(
            $this->toArray($row->get('s')),
            ['school' => $row->get('school')]
        ))->toArray();
    }

    public function find($id)
    {
        $row = $this->client->run(
            'MATCH (s:Student {id: $id})
             OPTIONAL MATCH (s)-[:ENROLLED_IN]->(sc:School)
             OPTIONAL MATCH (s)-[:STUDIES]->(sub:Subject)
             RETURN s, sc.name AS school, COLLECT(sub.name) AS subjects',
            ['id' => $id]
        )->first();

        return array_merge($this->toArray($row->get('s')), [
            'school'   => $row->get('school'),
            'subjects' => $row->get('subjects')->toArray(),
        ]);
    }

    public function create(array $data)
    {
        return $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($data) {
                $result = $tsx->run(
                    'CREATE (s:Student {id: randomUUID(), name: $name, email: $email})
                     RETURN s',
                    ['name' => $data['name'], 'email' => $data['email']]
                );
                return self::toArray($result->first()->get('s'));
            }
        );
    }

    public function update($id, array $data)
    {
        return $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($id, $data) {
                $result = $tsx->run(
                    'MATCH (s:Student {id: $id}) SET s += $props RETURN s',
                    ['id' => $id, 'props' => $data]
                );
                return self::toArray($result->first()->get('s'));
            }
        );
    }

    public function delete($id)
    {
        $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($id) {
                $tsx->run(
                    'MATCH (s:Student {id: $id}) DETACH DELETE s',
                    ['id' => $id]
                );
            }
        );
    }

    public function enrollInSchool($studentId, $schoolId)
    {
        return $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($studentId, $schoolId) {
                // remove old school first — student belongs to only one school
                $tsx->run(
                    'MATCH (s:Student {id: $sid})-[r:ENROLLED_IN]->() DELETE r',
                    ['sid' => $studentId]
                );
                $result = $tsx->run(
                    'MATCH (s:Student {id: $sid}), (sc:School {id: $scid})
                     CREATE (s)-[:ENROLLED_IN]->(sc)
                     RETURN s, sc.name AS school',
                    ['sid' => $studentId, 'scid' => $schoolId]
                );
                return array_merge(
                    self::toArray($result->first()->get('s')),
                    ['school' => $result->first()->get('school')]
                );
            }
        );
    }

    public function registerSubject($studentId, $subjectId)
    {
        return $this->client->writeTransaction(
            static function (TransactionInterface $tsx) use ($studentId, $subjectId) {
                $tsx->run(
                    'MATCH (s:Student {id: $sid}), (sub:Subject {id: $subid})
                     MERGE (s)-[:STUDIES]->(sub)',
                    ['sid' => $studentId, 'subid' => $subjectId]
                );
                $result = $tsx->run(
                    'MATCH (s:Student {id: $sid})
                     OPTIONAL MATCH (s)-[:STUDIES]->(sub:Subject)
                     RETURN s, COLLECT(sub.name) AS subjects',
                    ['sid' => $studentId]
                );
                return array_merge(
                    self::toArray($result->first()->get('s')),
                    ['subjects' => $result->first()->get('subjects')->toArray()]
                );
            }
        );
    }

    public function report()
    {
        return $this->client->run(
            'MATCH (s:Student)-[:ENROLLED_IN]->(sc:School)
             OPTIONAL MATCH (s)-[:STUDIES]->(sub:Subject)
             RETURN s.name AS student, sc.name AS school, COLLECT(sub.name) AS subjects
             ORDER BY s.name'
        )->map(fn($row) => [
            'student'  => $row->get('student'),
            'school'   => $row->get('school'),
            'subjects' => $row->get('subjects')->toArray(),
        ])->toArray();
    }

    private static function toArray($node): array
    {
        return [
            'id'    => $node->getProperty('id'),
            'name'  => $node->getProperty('name'),
            'email' => $node->getProperty('email'),
        ];
    }
}
