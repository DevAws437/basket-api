<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_id' => 'required|exists:teams,id',
            'jersey_number' => 'required|integer|min:0|max:99',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'position' => 'required|string|in:G,F,C,GF,FC,PG,SG,SF,PF',
            'photo' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'team_id.required' => 'الفريق مطلوب',
            'team_id.exists' => 'الفريق غير موجود',
            'jersey_number.required' => 'رقم القميص مطلوب',
            'jersey_number.integer' => 'رقم القميص يجب أن يكون رقماً',
            'jersey_number.max' => 'رقم القميص يجب ألا يتجاوز 99',
            'first_name.required' => 'الاسم الأول مطلوب',
            'last_name.required' => 'اسم العائلة مطلوب',
            'position.required' => 'المركز مطلوب',
            'position.in' => 'المركز غير صحيح (G, F, C, PG, SG, SF, PF, GF, FC)',
        ];
    }
}
