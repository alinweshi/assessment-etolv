<?php

namespace App\Http\Resources;


class SubjectResource extends BaseResource
{
    public function toArray($request): array
    {
        $data = $this->data();
        // dd($data); // Debugging line to inspect the data structure

        return [
            'id' => $data['id'] ?? null,
            'name' => $data['name'] ?? null,
            'created_at' => $this->formatDate($data['created_at'] ?? null),
            'updated_at' => $this->formatDate($data['updated_at'] ?? null),
        ];
    }
}
