<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */

    public function rules(): array
    {
        $studentId = $this->route('student');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],

            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('students', 'email')->ignore($studentId),
            ],

            'phone' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('students', 'phone')->ignore($studentId),
            ],

            'address' => ['sometimes', 'required', 'string', 'max:255'],

            'age' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],

            'gender' => ['sometimes', 'required', 'in:male,female'],

            'school_id' => [
                'sometimes',
                'required',
                Rule::exists('schools', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
