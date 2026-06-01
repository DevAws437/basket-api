<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:teams,name',
            'logo' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الفريق مطلوب',
            'name.unique' => 'اسم الفريق موجود مسبقاً',
            'name.max' => 'اسم الفريق يجب ألا يتجاوز 100 حرف',
        ];
    }
}
