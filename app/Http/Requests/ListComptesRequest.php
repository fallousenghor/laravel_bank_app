<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListComptesRequest extends FormRequest
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
        return [
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
            'type' => 'nullable|string|in:epargne,cheque',
            'statut' => 'nullable|string|in:actif,bloque,ferme',
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|string|in:dateCreation,solde,titulaire',
            'order' => 'nullable|string|in:asc,desc',
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
            'page.integer' => 'Le paramètre page doit être un entier.',
            'page.min' => 'Le paramètre page doit être au minimum 1.',
            'limit.integer' => 'Le paramètre limit doit être un entier.',
            'limit.min' => 'Le paramètre limit doit être au minimum 1.',
            'limit.max' => 'Le paramètre limit ne peut pas dépasser 100.',
            'type.in' => 'Le type doit être soit epargne soit cheque.',
            'statut.in' => 'Le statut doit être actif, bloque ou ferme.',
            'search.max' => 'La recherche ne peut pas dépasser 255 caractères.',
            'sort.in' => 'Le tri doit être dateCreation, solde ou titulaire.',
            'order.in' => 'L\'ordre doit être asc ou desc.',
            // 'admin_id' removed: admin access is done via authentication
        ];
    }
}
