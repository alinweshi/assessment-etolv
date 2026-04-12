<?php

namespace App\Http\Requests;

// app/Http/Requests/StudentIndexRequest.php
class StudentIndexRequest extends IndexRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'created_at' => ['sometimes', 'date', 'before_or_equal:today'],
            'updated_at' => ['sometimes', 'date', 'before_or_equal:today'],
            'gender'     => ['sometimes', 'string', 'in:male,female'],
            'age'        => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search'     => ['sometimes', 'string', 'max:255'],
        ]);
    }
}
