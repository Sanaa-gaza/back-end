<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * email is only required for a guest submitting the form; a logged-in
     * user's email is taken from their account instead (see
     * ContactMessageController::store).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:3', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'email' => [Rule::requiredIf(fn () => ! $this->user('sanctum')), 'nullable', 'email', 'max:255'],
            'subject' => ['required', Rule::in(['general', 'complaint', 'suggestion', 'support', 'partnership'])],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'الاسم الكامل مطلوب.',
            'full_name.min' => 'الاسم الكامل يجب أن يكون 3 أحرف على الأقل.',
            'full_name.max' => 'الاسم الكامل يجب ألا يتجاوز 100 حرف.',
            'city.required' => 'المدينة مطلوبة.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'subject.required' => 'الرجاء تحديد موضوع الرسالة.',
            'subject.in' => 'موضوع الرسالة غير صحيح.',
            'message.required' => 'نص الرسالة مطلوب.',
            'message.max' => 'نص الرسالة يجب ألا يتجاوز 2000 حرف.',
        ];
    }
}
