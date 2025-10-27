<?php

namespace App\Http\Requests;

use App\Rules\NciRule;
use App\Rules\TelephoneRule;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Admin can update accounts
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $compteId = $this->route('compteId');
        $compte = \App\Models\Compte::find($compteId);
        $userId = $compte ? $compte->utilisateur_id : null;

        return [
            'titulaire' => 'sometimes|string|max:255',
            'informationsClient.telephone' => [
                'sometimes',
                new TelephoneRule(),
                Rule::unique('users', 'telephone')->ignore($userId),
            ],
            'informationsClient.email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'informationsClient.password' => 'sometimes|string|min:8',
            'informationsClient.nci' => ['sometimes', 'nullable', new NciRule()],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            // Check if at least one field is provided for modification
            $hasTitulaire = isset($data['titulaire']);
            $hasTelephone = isset($data['informationsClient']['telephone']);
            $hasEmail = isset($data['informationsClient']['email']);
            $hasPassword = isset($data['informationsClient']['password']);
            $hasNci = isset($data['informationsClient']['nci']);

            if (!$hasTitulaire && !$hasTelephone && !$hasEmail && !$hasPassword && !$hasNci) {
                $validator->errors()->add('general', 'Au moins un champ de modification doit être fourni.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'titulaire.string' => 'Le titulaire doit être une chaîne de caractères.',
            'titulaire.max' => 'Le titulaire ne peut pas dépasser 255 caractères.',
            'informationsClient.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'informationsClient.email.email' => 'L\'email doit être valide.',
            'informationsClient.email.unique' => 'Cet email est déjà utilisé.',
            'informationsClient.password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'general' => 'Au moins un champ de modification doit être fourni.',
        ];
    }
}
