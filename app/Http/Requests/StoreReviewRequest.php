<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
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
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'الرجاء تحديد التقييم.',
            'rating.integer' => 'التقييم يجب أن يكون رقماً صحيحاً.',
            'rating.min' => 'التقييم يجب أن يكون نجمة واحدة على الأقل.',
            'rating.max' => 'التقييم يجب ألا يتجاوز 5 نجوم.',
            'comment.max' => 'التعليق يجب ألا يتجاوز 500 حرف.',
        ];
    }
}
