<?php

namespace App\Http\Requests;

use App\Services\ValidationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterSubjectRequest extends FormRequest
{
    public function __construct(private ValidationService $validation) {}
    public function authorize()
    {
        return true;
    }
    public function rules()
    {
        return [
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => $this->validation->subjectExists(),

        ];
    }
}
