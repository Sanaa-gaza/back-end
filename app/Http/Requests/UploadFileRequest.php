<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Open to guests: files (photos, licenses...) are uploaded during the
     * registration wizard, before the account exists.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:'.config('upload.max_size_kb'),
                'mimetypes:'.implode(',', config('upload.allowed_mimes')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'الملف مطلوب.',
            'file.file' => 'الملف غير صالح.',
            'file.max' => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت.',
            'file.mimetypes' => 'صيغة الملف غير مدعومة. الصيغ المسموحة: JPG, PNG, PDF.',
        ];
    }
}
