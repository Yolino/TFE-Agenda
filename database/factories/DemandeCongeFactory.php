<?php

namespace Database\Factories;

use App\Models\DemandeConge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DemandeConge>
 */
class DemandeCongeFactory extends Factory
{
    protected $model = DemandeConge::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Surchargé par `['user_id' => <int>]` dans les tests qui n'ont pas
            // besoin d'un vrai utilisateur en base.
            'user_id'    => User::factory(),
            'type'       => 'conge',
            'nb_jours'   => 3,
            'start_date' => '2024-01-01',
            'end_date'   => '2024-01-03',
            'status'     => 'en_cours',
        ];
    }

    /** Demande soumise, en attente de décision. */
    public function envoyee(): static
    {
        return $this->state(fn () => ['status' => 'envoyee']);
    }

    /** Demande déjà acceptée. */
    public function acceptee(): static
    {
        return $this->state(fn () => [
            'status'     => 'acceptee',
            'decided_by' => 999,
            'decided_at' => now(),
        ]);
    }

    /** Demande refusée. */
    public function refusee(): static
    {
        return $this->state(fn () => [
            'status'     => 'refusee',
            'decided_by' => 999,
            'decided_at' => now(),
        ]);
    }

    /** Demande annulée. */
    public function annulee(): static
    {
        return $this->state(fn () => [
            'status'       => 'annulee',
            'cancelled_at' => now(),
        ]);
    }
}
