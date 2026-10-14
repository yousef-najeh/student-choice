<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sometimes = $this->isMethod('put') || $this->isMethod('patch') ? 'sometimes' : 'required';
        $userId = $this->route('student')?->id;

        return [
            'name' => [$sometimes, 'string', 'max:255'],
            'email' => [$sometimes, 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'min:8'],
            'gpa' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tawjihi_branch_id' => ['nullable', 'exists:tawjihi_branches,id'],
            'preferred_region_id' => ['nullable', 'exists:regions,id'],
            'preferred_city_id' => ['nullable', 'exists:cities,id'],
            'budget_max' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'preferred_field_ids' => ['sometimes', 'array'],
            'preferred_field_ids.*' => ['integer', 'exists:fields_of_study,id'],
            'scholarship_ids' => ['sometimes', 'array'],
            'scholarship_ids.*' => ['integer', 'exists:scholarships,id'],
        ];
    }
}
