<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CongeRequest extends FormRequest
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
            'type' => 'required|string',
            'nb_jours' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type de congé est requis.',
            'type.string' => 'Le type de congé doit être une chaîne de caractères.',
            'nb_jours.required' => 'Le nombre de jours est requis.',
            'nb_jours.integer' => 'Le nombre de jours doit être un entier.',
            'start_date.required' => 'La date de début est requise.',
            'start_date.date' => 'La date de début doit être une date.',
            'end_date.required' => 'La date de fin est requise.',
            'end_date.date' => 'La date de fin doit être une date.',
        ];
    }
}
