<?php

namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'       => $this->id ?? $this['id'],
            'name'     => $this->name ?? $this['name'],
            'email'    => $this->email ?? $this['email'],
            'phone'    => $this->phone ?? $this['phone'],
            'address'  => $this->address ?? $this['address'],
            'age'      => $this->age ?? $this['age'],
            'gender'   => $this->gender ?? $this['gender'],
            'school'   => $this->whenLoaded(
                'school',
                fn() => new SchoolResource($this->school),
                $this['school'] ?? null
            ),
            'subjects' => $this->whenLoaded(
                'subjects',
                fn() => SubjectResource::collection($this->subjects),
                $this['subjects'] ?? []
            ),
            'created_at' => $this->created_at ?? $this['created_at'],
            'updated_at' => $this->updated_at ?? $this['updated_at'],
            'deleted_at' => $this->deleted_at ?? $this['deleted_at'],
        ];
    }
}
