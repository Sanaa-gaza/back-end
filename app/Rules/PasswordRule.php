<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PasswordRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)
            || mb_strlen($value) < 8
            || ! preg_match('/[A-Z]/', $value)
            || ! preg_match('/[0-9]/', $value)
            || ! preg_match('/[^A-Za-z0-9]/', $value)
        ) {
            $fail('كلمة المرور يجب أن تكون 8 أحرف على الأقل، وتحتوي على حرف كبير ورقم ورمز خاص واحد على الأقل.');
        }
    }
}
