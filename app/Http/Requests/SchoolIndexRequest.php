<?php

namespace App\Http\Requests;


class SchoolIndexRequest extends IndexRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'created_at' => ['sometimes', 'date', 'before_or_equal:today'],
            'updated_at' => ['sometimes', 'date', 'before_or_equal:today'],
            'search'     => ['sometimes', 'string', 'max:255'],
        ]);
    }
}
