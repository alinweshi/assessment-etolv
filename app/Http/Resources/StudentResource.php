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

            'school'   => isset($this->school)
                ? new SchoolResource($this->school)
                : null,

            'subjects' => isset($this->subjects)
                ? SubjectResource::collection($this->subjects)
                : [],


            'created_at' => $this->created_at ?? $this['created_at'] ?? null,
            'updated_at' => $this->updated_at ?? $this['updated_at'] ?? null,
            'deleted_at' => $this->deleted_at ?? $this['deleted_at'] ?? null,
        ];
    }
}
