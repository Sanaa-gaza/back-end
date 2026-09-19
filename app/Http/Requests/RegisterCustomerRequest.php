<?php

namespace App\Http\Requests;

use App\Models\Area;
use App\Rules\PasswordRule;
use App\Rules\PhoneRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RegisterCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the phone number (+972 -> +970) before validation, so the
     * uniqueness check and stored value both use the canonical form.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->phone)) {
            $this->merge([
                'phone' => PhoneRule::normalize($this->phone),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', new PhoneRule, 'unique:users,phone'],
            'password' => ['required', 'confirmed', new PasswordRule],
            'governorate_id' => ['required', 'exists:governorates,id'],
            'area_id' => ['required', 'exists:areas,id'],
            'terms_accepted' => ['accepted'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('governorate_id') || ! $this->filled('area_id')) {
                return;
            }

            $area = Area::find($this->area_id);

            if ($area && $area->governorate_id != $this->governorate_id) {
                $validator->errors()->add('area_id', 'عنوان السكن المختار لا يتبع للمحافظة المحددة.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'الاسم الكامل مطلوب.',
            'full_name.min' => 'الاسم الكامل يجب أن يكون 3 أحرف على الأقل.',
            'full_name.max' => 'الاسم الكامل يجب ألا يتجاوز 100 حرف.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.unique' => 'رقم الهاتف مستخدم بالفعل.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'governorate_id.required' => 'المحافظة مطلوبة.',
            'governorate_id.exists' => 'المحافظة المختارة غير صحيحة.',
            'area_id.required' => 'عنوان السكن مطلوب.',
            'area_id.exists' => 'عنوان السكن المختار غير صحيح.',
            'terms_accepted.accepted' => 'يجب الموافقة على الشروط والأحكام.',
        ];
    }
}
