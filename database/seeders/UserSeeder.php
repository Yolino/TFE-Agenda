<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un utilisateur admin de test
        User::factory()->admin()->create([
            'name' => 'Admin',
            'firstname' => 'Test',
            'email' => 'admin@test.com',
            'type' => 'I',
        ]);

        // Créer un utilisateur normal de test
        User::factory()->user()->create([
            'name' => 'User',
            'firstname' => 'Test',
            'email' => 'user@test.com',
            'type' => 'S',
        ]);

        // Créer 10 utilisateurs aléatoires
        User::factory(10)->create();
    }
}
