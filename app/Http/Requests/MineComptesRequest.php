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
            // Note: `user_id` query parameter support was removed. Use authenticated token
            // to identify the user for `/comptes/mine`.
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
            // No custom messages: route now requires authentication.
        ];
    }
}
