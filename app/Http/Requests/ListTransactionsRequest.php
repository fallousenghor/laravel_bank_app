<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListTransactionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'compte_id' => ['sometimes', 'uuid', 'exists:comptes,id'],
            'type' => ['sometimes', 'string', 'in:debit,credit'],
            'date_debut' => ['sometimes', 'date'],
            'date_fin' => ['sometimes', 'date', 'after_or_equal:date_debut'],
            'montant_min' => ['sometimes', 'numeric', 'min:0'],
            'montant_max' => ['sometimes', 'numeric', 'gt:montant_min'],
        ];
    }

    public function messages(): array
    {
        return [
            'compte_id.uuid' => "L'identifiant du compte doit être un UUID valide.",
            'compte_id.exists' => "Le compte spécifié n'existe pas.",
            'type.in' => "Le type doit être 'debit' ou 'credit'.",
            'date_debut.date' => "La date de début doit être une date valide.",
            'date_fin.date' => "La date de fin doit être une date valide.",
            'date_fin.after_or_equal' => "La date de fin doit être postérieure ou égale à la date de début.",
            'montant_min.numeric' => "Le montant minimum doit être un nombre.",
            'montant_min.min' => "Le montant minimum ne peut pas être négatif.",
            'montant_max.numeric' => "Le montant maximum doit être un nombre.",
            'montant_max.gt' => "Le montant maximum doit être supérieur au montant minimum.",
        ];
    }
}
