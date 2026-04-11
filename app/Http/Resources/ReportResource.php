<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ReportResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = $this->data();

        return [
            'student' => [
                'id' => $data['student']['id'] ?? null,
                'name' => $data['student']['name'] ?? null,
                'email' => $data['student']['email'] ?? null,
            ],
            'school' => isset($data['school'])
                ? [
                    'id' => $data['school']['id'] ?? null,
                    'name' => $data['school']['name'] ?? null,
                ]
                : null,
            'subjects' => collect($data['subjects'] ?? [])
                ->map(fn($subject) => [
                    'id' => $subject['id'] ?? null,
                    'name' => $subject['name'] ?? null,
                ])
                ->values()
                ->toArray(),
        ];
    }
}
