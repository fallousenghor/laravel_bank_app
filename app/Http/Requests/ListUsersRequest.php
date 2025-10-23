<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'string', 'in:admin,client'],
            'search' => ['sometimes', 'string', 'min:3'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => "Le rôle doit être 'admin' ou 'client'.",
            'search.min' => "Le terme de recherche doit contenir au moins 3 caractères.",
        ];
    }
}
