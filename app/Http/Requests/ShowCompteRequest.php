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
        // Le paramètre de route contient désormais le numéro de compte
        $this->merge(['id' => $this->route('id')]);

        return [
            // We accept a string account number. Ensure it exists in the `comptes.numero` column.
            'id' => 'required|string|exists:comptes,numero',
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
            'id.required' => 'Le numéro de compte est requis.',
            'id.string' => 'Le numéro de compte doit être une chaîne de caractères.',
            'id.exists' => 'Le numéro de compte spécifié n\'existe pas.',
        ];
    }
}
