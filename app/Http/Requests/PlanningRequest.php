<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanningRequest extends FormRequest
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
        // Statuts réservés aux admins : ne peuvent pas être posés via l'UI standard.
        $userStatuses = ['bureau', 'tele_travail', 'recup'];
        $adminOnlyStatuses = ['conge', 'css', 'indisponible', 'maladie', 'jour_ferie'];

        $allowedStatuses = auth()->check() && auth()->user()->is_admin()
            ? array_merge($userStatuses, $adminOnlyStatuses)
            : $userStatuses;

        return [
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'status' => 'required|in:' . implode(',', $allowedStatuses),
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'start_time_afternoon' => 'nullable|date_format:H:i',
            'end_time_afternoon' => 'nullable|date_format:H:i'
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
            'user_id.required' => 'L\'identifiant de l\'utilisateur est requis.',
            'user_id.exists' => 'L\'utilisateur n\'existe pas.',
            'date.required' => 'La date est requise.',
            'date.date' => 'La date n\'est pas valide.',
            'status.required' => 'Le statut est requis.',
            'status.in' => 'Le statut n\'est pas valide.',
            'start_time.date_format' => 'L\'heure de début n\'est pas valide.',
            'end_time.date_format' => 'L\'heure de fin n\'est pas valide.',
            'start_time_afternoon.date_format' => 'L\'heure de début n\'est pas valide.',
            'end_time_afternoon.date_format' => 'L\'heure de fin n\'est pas valide.'
        ];
    }
}
