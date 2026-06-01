<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:100|unique:teams,name,' . $this->route('team'),
            'logo' => 'nullable|string|max:255',
            'is_populated' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'اسم الفريق موجود مسبقاً',
            'name.max' => 'اسم الفريق يجب ألا يتجاوز 100 حرف',
        ];
    }
}
