<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\DatabaseTestCase;

class PlanningExcludesDirecteurTest extends DatabaseTestCase
{
    public function test_un_directeur_n_apparait_pas_dans_les_utilisateurs_de_l_agence(): void
    {
        $agenceId = 1;

        $directeur = User::factory()->create();
        $collaborateur = User::factory()->create();

        DB::connection('bti')->table('departements')->insert([
            ['id' => 1, 'letter' => User::DEPARTEMENT_DIRECTION_LETTER, 'nom' => 'Direction'],
            ['id' => 2, 'letter' => 'I', 'nom' => 'Informatique'],
        ]);

        DB::connection('bti')->table('departement_user')->insert([
            ['user_id' => $directeur->id, 'departement_id' => 1],
            ['user_id' => $collaborateur->id, 'departement_id' => 2],
        ]);

        DB::connection('bti')->table('pivot_a_u')->insert([
            ['user_id' => $directeur->id, 'agence_id' => $agenceId],
            ['user_id' => $collaborateur->id, 'agence_id' => $agenceId],
        ]);

        $method = new ReflectionMethod(AdminController::class, 'userIdsInAgence');
        $method->setAccessible(true);
        $ids = $method->invoke(new AdminController(), $agenceId)
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->assertContains((int) $collaborateur->id, $ids);
        $this->assertNotContains((int) $directeur->id, $ids);
    }
}
