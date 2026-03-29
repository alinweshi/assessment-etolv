<?php

namespace App\Http\Resources;

use App\Http\Resources\StudentResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id ?? $this['id'],
            'name' => $this->name ?? $this['name'],
            'address' => $this->address ?? $this['address'],
            'phone' => $this->phone ?? $this['phone'],
            'email' => $this->email ?? $this['email'],
            'website' => $this->website ?? $this['website'],
            'created_at' => $this->created_at ?? $this['created_at'],
            'updated_at' => $this->updated_at ?? $this['updated_at'],
            'deleted_at' => $this->deleted_at ?? $this['deleted_at'],
            'students' => StudentResource::collection($this->whenLoaded('students')),

        ];
    }
}
