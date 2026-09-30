<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class SoloLetras implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)
            || preg_match("/\A[\pL '.\-]+\z/u", $value) !== 1
            || preg_match('/  +/', $value) === 1
            || preg_match('/\pL/u', $value) !== 1) {
            $fail('El campo :attribute solo puede contener letras y espacios.');
        }
    }
}
