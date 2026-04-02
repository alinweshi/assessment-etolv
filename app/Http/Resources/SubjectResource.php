<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id ?? $this['id'],
            'name' => $this->name ?? $this['name'],
            'created_at' => $this->created_at ?? $this['created_at'] ?? null,
            'updated_at' => $this->updated_at ?? $this['updated_at'] ?? null,
            'deleted_at' => $this->deleted_at ?? $this['deleted_at'] ?? null,
        ];
    }
}
