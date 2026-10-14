<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FacultyRequest extends FormRequest
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
            'name' => [$sometimes, 'string', 'max:255'],
        ];
    }
}
