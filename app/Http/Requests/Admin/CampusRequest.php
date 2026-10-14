<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CampusRequest extends FormRequest
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
            'city_id' => [$sometimes, 'exists:cities,id'],
            'name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
