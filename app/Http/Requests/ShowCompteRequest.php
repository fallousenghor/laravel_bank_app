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
        // L'ID est passé dans l'URL, donc on le récupère des paramètres de route
        $this->merge(['id' => $this->route('id')]);

        return [
            'id' => 'required|string|uuid|exists:comptes,id',
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
            'id.required' => 'L\'ID du compte est requis.',
            'id.string' => 'L\'ID du compte doit être une chaîne de caractères.',
            'id.uuid' => 'L\'ID du compte doit être un UUID valide.',
            'id.exists' => 'Le compte spécifié n\'existe pas.',
        ];
    }
}
