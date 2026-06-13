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
        User::factory()->admin()->create([
            'name' => 'Admin',
            'firstname' => 'Test',
            'email' => 'admin@test.com',
            'type' => 'I',
        ]);

        User::factory()->user()->create([
            'name' => 'User',
            'firstname' => 'Test',
            'email' => 'user@test.com',
            'type' => 'S',
        ]);

        User::factory(10)->create();
    }
}
