<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'gpa' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'tawjihi_branch_id' => ['sometimes', 'nullable', 'exists:tawjihi_branches,id'],
            'preferred_region_id' => ['sometimes', 'nullable', 'exists:regions,id'],
            'preferred_city_id' => ['sometimes', 'nullable', 'exists:cities,id'],
            'budget_max' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'preferred_field_ids' => ['sometimes', 'array'],
            'preferred_field_ids.*' => ['integer', 'exists:fields_of_study,id'],
        ];
    }
}
