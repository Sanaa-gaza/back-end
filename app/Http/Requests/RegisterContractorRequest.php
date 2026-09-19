<?php

namespace App\Http\Requests;

use App\Models\Area;
use App\Rules\PasswordRule;
use App\Rules\PhoneRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Validator;

class RegisterContractorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
            'national_id' => ['required', 'digits:9', 'unique:users,national_id'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', new PhoneRule, 'unique:users,phone'],
            'password' => ['required', 'confirmed', new PasswordRule],
            'governorate_id' => ['required', 'exists:governorates,id'],
            'area_id' => ['required', 'exists:areas,id'],
            'workers_count' => ['required', 'integer', 'min:1'],
            'main_specialization' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'license_file_path' => ['required', 'string'],
            'terms_accepted' => ['accepted'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('governorate_id') && $this->filled('area_id')) {
                $area = Area::find($this->area_id);

                if ($area && $area->governorate_id != $this->governorate_id) {
                    $validator->errors()->add('area_id', 'عنوان السكن المختار لا يتبع للمحافظة المحددة.');
                }
            }

            if ($this->filled('license_file_path') && ! Storage::disk('public')->exists($this->license_file_path)) {
                $validator->errors()->add('license_file_path', 'رخصة المقاولة غير موجودة. الرجاء رفعها أولاً عبر /api/uploads.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'الاسم الكامل مطلوب.',
            'full_name.min' => 'الاسم الكامل يجب أن يكون 3 أحرف على الأقل.',
            'full_name.max' => 'الاسم الكامل يجب ألا يتجاوز 100 حرف.',
            'national_id.required' => 'رقم الهوية مطلوب.',
            'national_id.digits' => 'رقم الهوية يجب أن يتكون من 9 أرقام.',
            'national_id.unique' => 'رقم الهوية مستخدم بالفعل.',
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
            'workers_count.required' => 'عدد العمال مطلوب.',
            'workers_count.integer' => 'عدد العمال يجب أن يكون رقماً صحيحاً.',
            'workers_count.min' => 'عدد العمال يجب أن يكون 1 على الأقل.',
            'main_specialization.required' => 'مجال التخصص الرئيسي مطلوب.',
            'description.max' => 'النبذة التعريفية يجب ألا تتجاوز 500 حرف.',
            'license_file_path.required' => 'رخصة المقاولة مطلوبة.',
            'terms_accepted.accepted' => 'يجب الموافقة على الشروط والأحكام.',
        ];
    }
}
