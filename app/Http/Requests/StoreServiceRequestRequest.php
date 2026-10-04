<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'craftsman_id' => ['required', 'exists:craftsmen,id'],
            'service_id' => ['sometimes', 'nullable', 'exists:services,id'],
            'description' => ['required', 'string', 'max:1000'],
            'preferred_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'craftsman_id.required' => 'الرجاء تحديد الحرفي.',
            'craftsman_id.exists' => 'الحرفي المحدد غير موجود.',
            'service_id.exists' => 'الخدمة المحددة غير موجودة.',
            'description.required' => 'الرجاء وصف الخدمة المطلوبة.',
            'description.max' => 'الوصف يجب ألا يتجاوز 1000 حرف.',
            'preferred_date.date' => 'التاريخ المفضل غير صحيح.',
            'preferred_date.after_or_equal' => 'التاريخ المفضل يجب ألا يكون في الماضي.',
        ];
    }
}
