<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_id' => 'sometimes|exists:teams,id',
            'jersey_number' => 'sometimes|integer|min:0|max:99',
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'position' => 'sometimes|string|in:G,F,C,GF,FC,PG,SG,SF,PF',
            'photo' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'team_id.exists' => 'الفريق غير موجود',
            'jersey_number.integer' => 'رقم القميص يجب أن يكون رقماً',
            'jersey_number.max' => 'رقم القميص يجب ألا يتجاوز 99',
            'position.in' => 'المركز غير صحيح',
        ];
    }
}
