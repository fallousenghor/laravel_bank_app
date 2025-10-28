<?php

namespace App\Rules;

use App\Messages\fr\RuleMessages;
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
        // Validation personnalisée du NCI sénégalais sans regex
        // Format: 13 chiffres commençant par 1 ou 2

        // Vérifier que c'est une chaîne
        if (!is_string($value)) {
            $fail(RuleMessages::NCI_STRING->value);
            return;
        }

        // Supprimer les espaces éventuels
        $cleanValue = str_replace(' ', '', $value);

        // Vérifier la longueur exacte
        if (strlen($cleanValue) !== 13) {
            $fail(RuleMessages::NCI_LONGUEUR->value);
            return;
        }

        // Vérifier que tous les caractères sont des chiffres
        if (!ctype_digit($cleanValue)) {
            $fail(RuleMessages::NCI_CHIFFRES->value);
            return;
        }

        // Vérifier que le premier chiffre est 1 ou 2
        $firstDigit = $cleanValue[0];
        if ($firstDigit !== '1' && $firstDigit !== '2') {
            $fail(RuleMessages::NCI_COMMENCE_PAR_1_OU_2->value);
            return;
        }

        // Validation supplémentaire : vérifier que ce n'est pas une séquence répétée (comme 1111111111111)
        $uniqueDigits = count(array_unique(str_split($cleanValue)));
        if ($uniqueDigits < 3) {
            $fail(RuleMessages::NCI_SEQUENCE_REPETITIVE->value);
            return;
        }
    }
}
