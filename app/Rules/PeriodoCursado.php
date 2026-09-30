<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PeriodoCursado implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/\A([0-9]{4})(?:-([0-9]{4}))?\z/', $value, $matches) !== 1) {
            $fail('El período cursado debe ser un año AAAA o dos años consecutivos AAAA-AAAA, entre 1990 y el próximo año.');

            return;
        }

        $firstYear = (int) $matches[1];
        $lastYear = isset($matches[2]) ? (int) $matches[2] : $firstYear;
        $maximumYear = now()->year + 1;

        if ($firstYear < 1990 || $lastYear > $maximumYear || (isset($matches[2]) && $lastYear !== $firstYear + 1)) {
            $fail('El período cursado debe ser un año AAAA o dos años consecutivos AAAA-AAAA, entre 1990 y el próximo año.');
        }
    }
}
