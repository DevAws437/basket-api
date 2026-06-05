<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

<<<<<<< HEAD
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:teams,name',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }
=======
   public function rules(): array
{
    return [
        'name' => 'required|string|max:100|unique:teams,name',
        'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'is_populated' => 'nullable|boolean',
    ];
}
>>>>>>> 364522a36e8593377b3611f16ac81a7f66bea18b

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الفريق مطلوب',
            'name.unique' => 'اسم الفريق موجود مسبقاً',
            'name.max' => 'اسم الفريق يجب ألا يتجاوز 100 حرف',
        ];
    }
}
