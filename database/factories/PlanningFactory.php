<?php

namespace Database\Factories;

use App\Models\Planning;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Planning>
 */
class PlanningFactory extends Factory
{
    protected $model = Planning::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'date'             => '2024-01-01',
            'status_id'        => Planning::STATUS_MAP['bureau'],
            'demande_conge_id' => null,
        ];
    }

    /** Jour de planning rattaché à une demande de congé. */
    public function pourConge(int $demandeCongeId): static
    {
        return $this->state(fn () => [
            'status_id'        => Planning::STATUS_MAP['conge'],
            'demande_conge_id' => $demandeCongeId,
        ]);
    }
}
