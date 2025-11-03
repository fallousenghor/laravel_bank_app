<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Auth middleware already ensures user is authenticated
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:depot,retrait,virement'],
            // For depot/retrait use numero_compte; for virement use source_numero and destination_numero
            'numero_compte' => ['sometimes', 'string', 'exists:comptes,numero'],
            'source_numero' => ['sometimes', 'string', 'exists:comptes,numero'],
            'destination_numero' => ['sometimes', 'string', 'exists:comptes,numero'],
            'montant' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => "Le type de transaction est requis (depot, retrait, virement).",
            'type.in' => "Le type doit être 'depot', 'retrait' ou 'virement'.",
            'numero_compte.exists' => "Le numéro de compte spécifié n'existe pas.",
            'source_numero.exists' => "Le numéro de compte source spécifié n'existe pas.",
            'destination_numero.exists' => "Le numéro de compte destination spécifié n'existe pas.",
            'montant.required' => "Le montant est requis.",
            'montant.min' => "Le montant doit être supérieur à 0.",
        ];
    }
}
