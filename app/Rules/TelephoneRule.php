<?php

namespace App\Rules;

use App\Messages\fr\RuleMessages;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TelephoneRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Validation personnalisée du numéro de téléphone sénégalais sans regex
        // Formats acceptés: +221XXXXXXXXX, 221XXXXXXXXX, 77XXXXXXXX, 78XXXXXXXX, etc.

        // Vérifier que c'est une chaîne
        if (!is_string($value)) {
            $fail(RuleMessages::TELEPHONE_STRING->value);
            return;
        }

        // Supprimer les espaces et tirets éventuels
        $cleanValue = str_replace([' ', '-', '.', '_'], '', $value);

        // Gestion du préfixe +221 ou 221
        $hasPrefix = false;
        $phoneNumber = $cleanValue;

        if (str_starts_with($cleanValue, '+221')) {
            $hasPrefix = true;
            $phoneNumber = substr($cleanValue, 4); // Enlever +221
        } elseif (str_starts_with($cleanValue, '221')) {
            $hasPrefix = true;
            $phoneNumber = substr($cleanValue, 3); // Enlever 221
        }

        // Vérifier la longueur du numéro sans préfixe
        if (strlen($phoneNumber) !== 9) {
            $fail(RuleMessages::TELEPHONE_LONGUEUR->value);
            return;
        }

        // Vérifier que tous les caractères sont des chiffres
        if (!ctype_digit($phoneNumber)) {
            $fail(RuleMessages::TELEPHONE_CHIFFRES->value);
            return;
        }

        // Vérifier que le numéro commence par un indicatif valide (70, 76, 77, 78)
        $firstTwoDigits = substr($phoneNumber, 0, 2);
        $validPrefixes = ['70', '76', '77', '78'];

        if (!in_array($firstTwoDigits, $validPrefixes)) {
            $fail(RuleMessages::TELEPHONE_INDICATIF->value);
            return;
        }

        // Validation supplémentaire : éviter les numéros séquentiels évidents
        if ($phoneNumber === '777777777' || $phoneNumber === '000000000') {
            $fail(RuleMessages::TELEPHONE_INVALIDE->value);
            return;
        }
    }
}
