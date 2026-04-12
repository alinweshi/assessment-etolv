<?php

namespace App\Http\Requests;

class SubjectIndexRequest extends IndexRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'search' => ['sometimes', 'string', 'max:255'],
        ]);
    }
}
