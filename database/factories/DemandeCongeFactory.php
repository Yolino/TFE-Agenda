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
            'user_id'    => User::factory(),
            'type'       => 'conge',
            'nb_jours'   => 3,
            'start_date' => '2024-01-01',
            'end_date'   => '2024-01-03',
            'status'     => 'en_cours',
        ];
    }

    public function envoyee(): static
    {
        return $this->state(fn () => ['status' => 'envoyee']);
    }

    public function acceptee(): static
    {
        return $this->state(fn () => [
            'status'     => 'acceptee',
            'decided_by' => 999,
            'decided_at' => now(),
        ]);
    }

    public function refusee(): static
    {
        return $this->state(fn () => [
            'status'     => 'refusee',
            'decided_by' => 999,
            'decided_at' => now(),
        ]);
    }

    public function annulee(): static
    {
        return $this->state(fn () => [
            'status'       => 'annulee',
            'cancelled_at' => now(),
        ]);
    }
}
