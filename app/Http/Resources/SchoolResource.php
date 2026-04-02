<?php

namespace App\Http\Resources;

use App\Http\Resources\StudentResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id ?? $this['id'] ?? null,
            'name' => $this->name ?? $this['name'] ?? null,
            'address' => $this->address ?? $this['address'] ?? null,
            'phone' => $this->phone ?? $this['phone'] ?? null,
            'email' => $this->email ?? $this['email'] ?? null,
            'website' => $this->website ?? $this['website'] ?? null,
            'created_at' => $this->created_at ?? $this['created_at'] ?? null,
            'updated_at' => $this->updated_at ?? $this['updated_at'] ?? null,
            'deleted_at' => $this->deleted_at ?? $this['deleted_at'] ?? null,
            'students' => isset($this->students)
                ? StudentResource::collection($this->students)
                : [],
        ];
    }
}
