<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Laudis\Neo4j\Types\DateTime as Neo4jDateTime;
use Carbon\Carbon;

class StudentResource extends BaseResource
{
    public function toArray($request): array
    {
        // If the repository always uses self::toArray(), $this->resource is always an array
        $data = $this->data();
        $studentData = $data['student'];
        return [
            'id' => $studentData['id'] ?? null,
            'name' => $studentData['name'] ?? null,
            'email' => $studentData['email'] ?? null,
            'phone' => $studentData['phone'] ?? null,
            'address' => $studentData['address'] ?? null,
            'age' => $studentData['age'] ?? null,
            'gender' => $studentData['gender'] ?? null,

            // Relationships
            'school' => new SchoolResource($this->resource['school'] ?? null),
            'subjects' => SubjectResource::collection($this->resource['subjects'] ?? []),

            'created_at' => $this->formatDate($studentData['created_at'] ?? null),
            'updated_at' => $this->formatDate($studentData['updated_at'] ?? null),
        ];
    }
}
