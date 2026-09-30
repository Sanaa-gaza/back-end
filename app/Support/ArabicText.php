<?php

namespace App\Support;

class ArabicText
{
    /**
     * Normalize a free-text Arabic craft name so that trivial spelling
     * variations (diacritics, alef/hamza forms, extra whitespace) resolve
     * to the same canonical value before matching against `services.name`.
     */
    public static function normalize(string $text): string
    {
        $text = trim($text);

        // Strip Arabic diacritics (tashkeel) and tatweel.
        $text = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $text);

        // Unify alef variants (أ إ آ ٱ) to bare alef.
        $text = preg_replace('/[\x{0622}\x{0623}\x{0625}\x{0671}]/u', 'ا', $text);

        // Unify alef maksura (ى) to yaa (ي).
        $text = str_replace('ى', 'ي', $text);

        // Collapse repeated whitespace to a single space.
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }
}
