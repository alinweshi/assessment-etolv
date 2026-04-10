<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Laudis\Neo4j\Types\DateTime as Neo4jDateTime;
use Carbon\Carbon;

class StudentResource extends JsonResource
{
    public function toArray($request): array
    {
        // If the repository always uses self::toArray(), $this->resource is always an array
        $studentData = $this->resource['student'] ?? $this->resource;

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

    private function formatDate(mixed $value): ?string
    {
        if (!$value) return null;

        return match (true) {
            $value instanceof Carbon => $value->toDateTimeString(),
            $value instanceof Neo4jDateTime => Carbon::createFromTimestamp($value->getSeconds())->toDateTimeString(),
            is_string($value) => Carbon::parse($value)->toDateTimeString(),
            default => null,
        };
    }
}
