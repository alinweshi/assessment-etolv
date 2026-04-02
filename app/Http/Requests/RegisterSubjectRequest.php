<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterSubjectRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    public function rules()
    {
        return [
            // 'subject_ids' => 'required|array|min:1',
            // 'subject_ids.*' => ['required', Rule::exists('subjects', 'id')->whereNull('deleted_at')]
        ];
    }
}
