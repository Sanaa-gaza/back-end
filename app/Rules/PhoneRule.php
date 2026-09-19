<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PhoneRule implements ValidationRule
{
    private const PATTERN = '/^\+(970|972)(59|56)\d{7}$/';

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match(self::PATTERN, $value)) {
            $fail('رقم الهاتف يجب أن يكون رقماً فلسطينياً صحيحاً، مثال: +970591234567 أو +972591234567.');
        }
    }

    /**
     * Normalize an already-valid Palestinian phone number to the canonical
     * +970XXXXXXXXX form for storage and uniqueness checks, regardless of
     * whether the client submitted it with +970 or +972.
     */
    public static function normalize(string $phone): string
    {
        return preg_replace('/^\+972/', '+970', $phone);
    }
}
