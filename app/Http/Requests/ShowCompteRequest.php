<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowCompteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => ['required', 'uuid', 'exists:comptes,id'],
        ];
    }

    public function messages()
    {
        return [
            'id.required' => "L'identifiant du compte est requis.",
            'id.uuid' => "L'identifiant doit être un UUID valide.",
            'id.exists' => "Le compte n'existe pas.",
        ];
    }
}
