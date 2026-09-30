<?php

namespace App\Http\Requests;

use App\Models\Area;
use App\Rules\PasswordRule;
use App\Rules\PhoneRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Validator;

class RegisterInstitutionRequest extends FormRequest
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
            'institution_name' => ['required', 'string', 'max:200'],
            'commercial_registration_number' => ['required', 'string', 'unique:institutions,commercial_registration_number'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', new PhoneRule, 'unique:users,phone'],
            'password' => ['required', 'confirmed', new PasswordRule],
            'governorate_id' => ['required', 'exists:governorates,id'],
            'area_id' => ['required', 'exists:areas,id'],
            'activity_type' => ['required', 'string', 'max:200'],
            'license_file_path' => ['required', 'string'],
            'description' => ['required', 'string', 'max:500'],
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
                $validator->errors()->add('license_file_path', 'الرخصة التجارية غير موجودة. الرجاء رفعها أولاً عبر /api/uploads.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'institution_name.required' => 'اسم المؤسسة مطلوب.',
            'commercial_registration_number.required' => 'رقم التسجيل التجاري مطلوب.',
            'commercial_registration_number.unique' => 'رقم التسجيل التجاري مستخدم بالفعل.',
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
            'activity_type.required' => 'طبيعة النشاط مطلوبة.',
            'activity_type.max' => 'طبيعة النشاط يجب ألا تتجاوز 200 حرف.',
            'license_file_path.required' => 'الرخصة التجارية مطلوبة.',
            'description.required' => 'النبذة التعريفية مطلوبة.',
            'description.max' => 'النبذة التعريفية يجب ألا تتجاوز 500 حرف.',
            'terms_accepted.accepted' => 'يجب الموافقة على الشروط والأحكام.',
        ];
    }
}
