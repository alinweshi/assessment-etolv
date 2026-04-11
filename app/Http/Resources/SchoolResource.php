<?php

namespace App\Http\Resources;

use App\Http\Resources\StudentResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolResource extends BaseResource
{
    public function toArray($request): array
    {
        $data = $this->data();
        return [
            'id' => $data['id'] ?? null,
            'name' => $data['name'] ?? null,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'website' => $data['website'] ?? null,
            'created_at' => $this->formatDate($data['created_at'] ?? null),
            'updated_at' => $this->formatDate($data['updated_at'] ?? null),
            'deleted_at' => $this->formatDate($data['deleted_at'] ?? null),
            'students' => isset($this->students)
                ? StudentResource::collection($this->students)
                : [],
        ];
    }
}
