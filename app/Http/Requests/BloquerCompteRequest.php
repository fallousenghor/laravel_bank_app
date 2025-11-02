<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BloquerCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Admin only, but we'll check in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // allow scheduling in the future or immediate block (now)
            'date_debut_blocage' => 'required|date|after_or_equal:now',
            'date_fin_blocage' => 'required|date|after_or_equal:date_debut_blocage',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [

            'date_debut_blocage.required' => 'La date de début de blocage est obligatoire.',
            'date_debut_blocage.date' => 'La date de début de blocage doit être une date valide.',
            'date_debut_blocage.after_or_equal' => 'La date de début de blocage doit être maintenant ou dans le futur.',
            'date_fin_blocage.required' => 'La date de fin de blocage est obligatoire.',
            'date_fin_blocage.date' => 'La date de fin de blocage doit être une date valide.',
            'date_fin_blocage.after_or_equal' => 'La date de fin de blocage doit être égale ou postérieure à la date de début.',
        ];
    }
}
