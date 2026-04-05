<?php

namespace App\Http\Requests;

use App\Services\ValidationService;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function __construct(private ValidationService $validation) {}

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255',  $this->validation->uniqueStudentEmail()],
            'phone'     => ['required', 'string', 'max:20',  $this->validation->uniqueStudentPhone()],
            'address'   => ['required', 'string', 'max:255'],
            'age'       => ['required', 'integer', 'min:1', 'max:100'],
            'gender'    => ['required', 'string', 'in:male,female'],
            'school_id' => ['nullable', 'string',            $this->validation->schoolExists()],
        ];
    }
}
