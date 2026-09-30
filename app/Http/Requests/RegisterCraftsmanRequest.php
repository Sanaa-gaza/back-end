<?php

namespace App\Http\Requests;

use App\Models\Area;
use App\Models\Service;
use App\Rules\PasswordRule;
use App\Rules\PhoneRule;
use App\Support\ArabicText;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Validator;

class RegisterCraftsmanRequest extends FormRequest
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
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', new PhoneRule, 'unique:users,phone'],
            'password' => ['required', 'confirmed', new PasswordRule],
            'governorate_id' => ['required', 'exists:governorates,id'],
            'area_id' => ['required', 'exists:areas,id'],
            'personal_photo_path' => ['required', 'string'],
            'id_selfie_path' => ['required', 'string'],
            'craft_id' => ['required_without:custom_craft_name', 'nullable', 'exists:services,id'],
            'custom_craft_name' => ['required_without:craft_id', 'nullable', 'string', 'max:100'],
            'bio' => ['required', 'string', 'min:10', 'max:500'],
            'terms_accepted' => ['accepted'],
        ];
    }

    /**
     * When the craftsman typed a custom craft name instead of picking one
     * from the list, resolve it to a craft_id: reuse an existing service
     * if a normalized match exists, otherwise create one on the fly.
     */
    protected function passedValidation(): void
    {
        if (! $this->filled('craft_id') && $this->filled('custom_craft_name')) {
            $normalized = ArabicText::normalize($this->custom_craft_name);

            $service = Service::firstOrCreate(
                ['name' => $normalized],
                ['is_active' => true]
            );

            $this->merge(['craft_id' => $service->id]);
        }
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

            if ($this->filled('personal_photo_path') && ! Storage::disk('public')->exists($this->personal_photo_path)) {
                $validator->errors()->add('personal_photo_path', 'الصورة الشخصية غير موجودة. الرجاء رفعها أولاً عبر /api/uploads.');
            }

            if ($this->filled('id_selfie_path') && ! Storage::disk('public')->exists($this->id_selfie_path)) {
                $validator->errors()->add('id_selfie_path', 'صورة الهوية غير موجودة. الرجاء رفعها أولاً عبر /api/uploads.');
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
            'personal_photo_path.required' => 'الصورة الشخصية مطلوبة.',
            'id_selfie_path.required' => 'صورة الهوية مطلوبة.',
            'craft_id.required_without' => 'الحرفة مطلوبة.',
            'craft_id.exists' => 'الحرفة المختارة غير صحيحة.',
            'custom_craft_name.required_without' => 'اسم الحرفة مطلوب.',
            'bio.required' => 'النبذة التعريفية مطلوبة.',
            'bio.min' => 'النبذة التعريفية يجب أن تكون 10 أحرف على الأقل.',
            'bio.max' => 'النبذة يجب ألا تتجاوز 500 حرف.',
            'terms_accepted.accepted' => 'يجب الموافقة على الشروط والأحكام.',
        ];
    }
}
