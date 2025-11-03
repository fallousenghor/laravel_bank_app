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
            // `numero_compte` in the query is the account number (comptes.numero).
            // Validate as string and ensure the numero exists in the comptes table.
            'numero_compte' => ['sometimes', 'string', 'exists:comptes,numero'],
            'type' => ['sometimes', 'string', 'in:debit,credit,depot,retrait'],
            'date_fin' => ['sometimes', 'date'],
            'montant_min' => ['sometimes', 'numeric', 'min:0'],
            'montant_max' => ['sometimes', 'numeric', 'gt:montant_min'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string'],
            'order' => ['sometimes', 'string', 'in:asc,desc'],
            'search' => ['sometimes', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero_compte.string' => "Le numéro de compte doit être une chaîne.",
            'numero_compte.exists' => "Le compte spécifié n'existe pas (vérifiez le numéro).",
            'type.in' => "Le type doit être 'debit' ou 'credit'.",
            'date_fin.date' => "La date de fin doit être une date valide.",
            'montant_min.numeric' => "Le montant minimum doit être un nombre.",
            'montant_min.min' => "Le montant minimum ne peut pas être négatif.",
            'montant_max.numeric' => "Le montant maximum doit être un nombre.",
            'montant_max.gt' => "Le montant maximum doit être supérieur au montant minimum.",
            'page.integer' => "Le numéro de page doit être un entier.",
            'page.min' => "Le numéro de page doit être au moins 1.",
            'per_page.integer' => "Le nombre d'éléments par page doit être un entier.",
            'per_page.min' => "Le nombre d'éléments par page doit être au moins 1.",
            'per_page.max' => "Le nombre d'éléments par page ne peut pas dépasser 100.",
            'order.in' => "L'ordre de tri doit être 'asc' ou 'desc'.",
            'sort.string' => "Le champ de tri doit être une chaîne.",
            'search.string' => "Le filtre de recherche doit être une chaîne.",
        ];
    }
}
