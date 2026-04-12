<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
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
        if ($this->method() === 'PATCH') {
            return [
                'firstname' => ['required', 'string', 'max:20'],
                'name' => ['required', 'string', 'max:20'],
                'email' => ['required', 'email:rfc,dns', 'max:30'],
                "password" => ["nullable", "min:8"],
            ];
        }
        return [
            'firstname' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email:rfc,dns', 'max:30'],
            'password' => ['required', 'min:8'],
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
            'firstname.required' => 'Le prénom est requis.',
            'firstname.string' => 'Le prénom doit être une chaîne de caractères.',
            'firstname.max' => 'Le prénom ne doit pas dépasser :max caractères.',
            'name.required' => 'Le nom est requis.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne doit pas dépasser :max caractères.',
            'email.required' => 'L\'email est requis.',
            'email.email' => 'L\'email doit être une adresse email valide.',
            'email.max' => 'L\'email ne doit pas dépasser :max caractères.',
            'password.required' => 'Le mot de passe est requis.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
        ];
    }
}
