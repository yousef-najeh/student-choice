<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class InstitutionRequest extends FormRequest
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
            'institution_type' => ['nullable', 'string', 'max:255'],
            'official_website' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
