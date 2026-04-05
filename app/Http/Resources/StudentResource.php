<?php

namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends BaseResource
{
    public function toArray($request): array
    {
        $data = $this->data();

        return [
            'id'       => $data['id'] ?? $this->getId() ?? null,
            'name'     => $data['name'] ?? null,
            'email'    => $data['email'] ?? null,
            'phone'    => $data['phone'] ?? null,
            'address'  => $data['address'] ?? null,
            'age'      => $data['age'] ?? null,
            'gender'   => $data['gender'] ?? $this['gender'],

            'school'   => isset($this->school)
                ? new SchoolResource($this->school)
                : null,

            'subjects' => isset($this->subjects)
                ? SubjectResource::collection($this->subjects)
                : [],


            'created_at' => $data['created_at'] ?? null,
            'updated_at' => $data['updated_at'] ?? null,
            'deleted_at' => $data['deleted_at'] ?? null,
        ];
    }
}
