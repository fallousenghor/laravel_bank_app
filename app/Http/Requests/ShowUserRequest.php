<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function messages()
    {
        return [
            'id.required' => "L'identifiant de l'utilisateur est requis.",
            'id.integer' => "L'identifiant doit être un entier.",
            'id.exists' => "L'utilisateur n'existe pas.",
        ];
    }
}
