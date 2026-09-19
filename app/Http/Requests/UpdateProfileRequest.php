<?php

namespace App\Http\Requests;

use App\Models\Area;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Only ever these fields — locked fields (national_id,
     * commercial_registration_number, craft_id, license_file_path) are
     * intentionally absent here so they can never be mass-assigned, even if
     * the client sends them.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'string', 'min:3', 'max:100'],
            'governorate_id' => ['sometimes', 'exists:governorates,id'],
            'area_id' => ['sometimes', 'exists:areas,id'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
            'personal_photo_path' => ['sometimes', 'string'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'main_specialization' => ['sometimes', 'string', 'max:200'],
            'workers_count' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('governorate_id') && ! $this->filled('area_id')) {
                $validator->errors()->add('area_id', 'الرجاء تحديد عنوان السكن أيضاً عند تغيير المحافظة.');

                return;
            }

            if ($this->filled('area_id')) {
                $governorateId = $this->filled('governorate_id')
                    ? $this->governorate_id
                    : $this->user()->governorate_id;

                $area = Area::find($this->area_id);

                if ($area && $area->governorate_id != $governorateId) {
                    $validator->errors()->add('area_id', 'عنوان السكن المختار لا يتبع للمحافظة المحددة.');
                }
            }

            if ($this->filled('personal_photo_path') && ! Storage::disk('public')->exists($this->personal_photo_path)) {
                $validator->errors()->add('personal_photo_path', 'الصورة غير موجودة. الرجاء رفعها أولاً عبر /api/uploads.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'full_name.min' => 'الاسم الكامل يجب أن يكون 3 أحرف على الأقل.',
            'full_name.max' => 'الاسم الكامل يجب ألا يتجاوز 100 حرف.',
            'governorate_id.exists' => 'المحافظة المختارة غير صحيحة.',
            'area_id.exists' => 'عنوان السكن المختار غير صحيح.',
            'bio.max' => 'النبذة يجب ألا تتجاوز 500 حرف.',
            'description.max' => 'النبذة التعريفية يجب ألا تتجاوز 500 حرف.',
            'workers_count.integer' => 'عدد العمال يجب أن يكون رقماً صحيحاً.',
            'workers_count.min' => 'عدد العمال يجب أن يكون 1 على الأقل.',
        ];
    }
}
