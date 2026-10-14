<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ScholarshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sometimes = $this->isMethod('put') || $this->isMethod('patch') ? 'sometimes' : 'required';

        return [
            'name' => [$sometimes, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'eligibility_notes' => ['nullable', 'string'],
        ];
    }
}
