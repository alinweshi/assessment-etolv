<?php

namespace App\Http\Requests;

use App\Services\ValidationService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
{
    public function __construct(private ValidationService $validation) {}

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('student'); // current student being updated

        return [
            'name'      => ['sometimes', 'string', 'max:255'],
            'email'     => ['sometimes', 'email', 'max:255',  $this->validation->uniqueStudentEmail($id)],
            'phone'     => ['sometimes', 'string', 'max:20',  $this->validation->uniqueStudentPhone($id)],
            'address'   => ['sometimes', 'string', 'max:255'],
            'age'       => ['sometimes', 'integer', 'min:1', 'max:100'],
            'gender'    => ['sometimes', 'string', 'in:male,female'],
            'school_id' => ['sometimes', 'string',            $this->validation->schoolExists()],
        ];
    }
}
