<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only admins are allowed to search clients.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && isset($user->role) && $user->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     * Require at least one of `telephone` or `nci`.
     */
    public function rules(): array
    {
        return [
            'telephone' => 'required_without:nci|string',
            'nci' => 'required_without:telephone|string',
        ];
    }

    public function messages(): array
    {
        return [
            'telephone.required_without' => 'Le téléphone est requis si le NCI n\'est pas fourni.',
            'nci.required_without' => 'Le NCI est requis si le téléphone n\'est pas fourni.',
        ];
    }
}
