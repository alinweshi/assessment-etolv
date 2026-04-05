<?php

namespace App\Http\Requests;


use App\Services\ValidationService;
use Illuminate\Foundation\Http\FormRequest;

class EnrollStudentRequest extends FormRequest
{
    public function __construct(private ValidationService $validation) {}

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'student'   => ['required', 'string', $this->validation->studentExists()],

            'school_id' => ['required', 'string', $this->validation->schoolExists()],
        ];
    }

    protected function prepareForValidation(): void
    {
        // only merge the route param, school_id comes from body
        $this->merge([
            'student' => $this->route('student'),
        ]);
    }
}
