<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MajorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sometimes = $this->isMethod('put') || $this->isMethod('patch') ? 'sometimes' : 'required';

        return [
            'institution_id' => [$sometimes, 'exists:institutions,id'],
            'faculty_id' => ['nullable', 'exists:faculties,id'],
            'degree_level_id' => ['nullable', 'exists:degree_levels,id'],
            'field_of_study_id' => ['nullable', 'exists:fields_of_study,id'],
            'name' => [$sometimes, 'string', 'max:255'],
            'credit_hour_price' => ['nullable', 'numeric', 'min:0'],
            'credit_hours' => ['nullable', 'integer', 'min:0'],
            'study_duration_text' => ['nullable', 'string', 'max:255'],
            'admission_average' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'min_gpa_to_apply' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'admission_link' => ['nullable', 'string', 'max:2048'],
            'campus_ids' => ['sometimes', 'array'],
            'campus_ids.*' => ['integer', 'exists:campuses,id'],
            'tawjihi_branch_ids' => ['sometimes', 'array'],
            'tawjihi_branch_ids.*' => ['integer', 'exists:tawjihi_branches,id'],
        ];
    }
}
