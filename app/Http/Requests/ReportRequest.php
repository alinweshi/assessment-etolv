<?php

namespace App\Http\Requests;

use App\Services\ValidationService;
use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    public function __construct(private ValidationService $validation) {}
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'from' => ['sometimes', 'date', 'before_or_equal:to'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'school_id' => ['nullable', 'string',            $this->validation->schoolExists()],
            'student_id' => ['nullable', 'string',            $this->validation->studentExists()],
        ];
    }
}
