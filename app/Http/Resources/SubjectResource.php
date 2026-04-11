<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends BaseResource
{
    public function toArray($request): array
    {
        $data = $this->data();

        return [
            'id' => $data['id'] ?? null,
            'name' => $data['name'] ?? null,
            'created_at' => $data['created_at'] ?? null,
            'created_at' => $this->formatDate($data['created_at'] ?? null),
            'updated_at' => $this->formatDate($data['updated_at'] ?? null),
        ];
    }
}
