<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    /**
     * État par défaut, aligné sur le schéma réel de la table "users" (base bti).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'              => fake()->lastName(),
            'firstname'         => fake()->firstName(),
            'alias'             => null,
            'phone'             => fake()->optional()->numerify('04########'),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'acces_level'       => 'U',
            'theme'             => 'light',
            'actif'             => true,
            'remember_token'    => Str::random(10),
        ];
    }

    /** Adresse e-mail non vérifiée. */
    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    /** Utilisateur de type "étudiant" (niveau d'accès dédié). */
    public function etudiant(): static
    {
        return $this->state(fn () => ['acces_level' => User::ROLE_ETUDIANT]);
    }

    /** Compte désactivé. */
    public function inactif(): static
    {
        return $this->state(fn () => ['actif' => false]);
    }
}
