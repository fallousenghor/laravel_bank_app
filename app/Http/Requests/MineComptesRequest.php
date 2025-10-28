<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MineComptesRequest extends FormRequest
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
            // Users in this app use UUIDs as primary keys, accept UUID strings
            'user_id' => ['nullable', 'string', 'uuid', 'exists:users,id'],
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
            'user_id.uuid' => 'L\'ID utilisateur doit être un UUID valide.',
            'user_id.exists' => 'L\'utilisateur spécifié n\'existe pas.',
        ];
    }
}
