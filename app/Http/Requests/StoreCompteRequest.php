<?php

namespace App\Http\Requests;

use App\Rules\NciRule;
use App\Rules\TelephoneRule;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        // Only admins are allowed to create new comptes
        return $user && isset($user->role) && $user->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $clientId = $this->input('client.id');
        $telephone = $this->input('client.telephone');

        // If telephone corresponds to an existing user, allow unique rules to ignore that user
        $existingUser = null;
        if ($telephone) {
            $existingUser = User::where('telephone', $telephone)->first();
        }

        $ignoreId = $clientId ?? ($existingUser->id ?? null);

        return [
            'type' => 'required|in:cheque,epargne',
            'solde' => 'required|numeric|min:10000',
            // 'default' is not a built-in Laravel validation rule. Set default in prepareForValidation().
            'devise' => 'sometimes|string',
            'client.id' => 'nullable|integer|exists:users,id',
            'client.titulaire' => 'required|string|max:255',
            'client.nci' => ['nullable', new NciRule()],
            'client.email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($ignoreId),
            ],
            'client.telephone' => [
                'required',
                new TelephoneRule(),
                Rule::unique('users', 'telephone')->ignore($ignoreId),
            ],
            'client.adresse' => 'required|string|max:500',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * Ensure a default currency is provided when none is sent.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->has('devise') || $this->input('devise') === null) {
            $this->merge([
                'devise' => 'FCFA',
            ]);
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type doit être cheque ou epargne.',
            'solde.required' => 'Le solde initial est obligatoire.',
            'solde.numeric' => 'Le solde doit être un nombre.',
            'solde.min' => 'Le solde initial doit être d\'au moins 10 000 FCFA.',
            'client.titulaire.required' => 'Le nom du titulaire est requis.',
            'client.email.required' => 'L\'email est requis.',
            'client.email.email' => 'L\'email doit être valide.',
            'client.email.unique' => 'Cet email est déjà utilisé.',
            'client.telephone.required' => 'Le numéro de téléphone est requis.',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'client.adresse.required' => 'L\'adresse est requise.',
        ];
    }
}
