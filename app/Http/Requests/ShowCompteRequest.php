<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // The route parameter now contains the account number 'numero'
        $this->merge(['numero' => $this->route('numero')]);

        return [
            // Accept a string account number and ensure it exists in the `comptes.numero` column.
            'numero' => 'required|string|exists:comptes,numero',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'numero.required' => 'Le numéro de compte est requis.',
            'numero.string' => 'Le numéro de compte doit être une chaîne de caractères.',
            'numero.exists' => 'Le numéro de compte spécifié n\'existe pas.',
        ];
    }
}
