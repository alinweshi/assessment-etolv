<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'student'  => $this['student'],
            'school'   => $this['school'],
            'subjects' => $this['subjects'],
        ];
    }
}
