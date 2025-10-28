<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NciRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Senegalese NCI (Carte Nationale d'Identité) validation
        // Format: 13 digits starting with 1 or 2
        $pattern = '/^[12][0-9]{12}$/';

        if (!preg_match($pattern, $value)) {
            $fail('Le NCI doit être un numéro sénégalais valide (13 chiffres commençant par 1 ou 2).');
        }
    }
}
