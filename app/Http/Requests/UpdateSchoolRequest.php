<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolRequest extends FormRequest
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
        $schoolId = $this->route('school');

        return [
            'name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|nullable|string|max:255',
            'phone' => 'sometimes|required|string|max:255|unique:schools,phone,' . $schoolId,
            'email' => 'sometimes|required|email|max:255|unique:schools,email,' . $schoolId,
            'website' => 'sometimes|required|url|max:255',
        ];
    }
}
