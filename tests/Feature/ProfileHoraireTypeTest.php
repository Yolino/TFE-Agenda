<?php

namespace Tests\Feature;

use App\Http\Controllers\ProfileController;
use App\Models\PlanningTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\DatabaseTestCase;

class ProfileHoraireTypeTest extends DatabaseTestCase
{
    private function rattacherADirection(User $user): void
    {
        DB::connection('bti')->table('departements')->insertOrIgnore([
            ['id' => 1, 'letter' => User::DEPARTEMENT_DIRECTION_LETTER, 'nom' => 'Direction'],
        ]);
        DB::connection('bti')->table('departement_user')->insert([
            'user_id'        => $user->id,
            'departement_id' => 1,
        ]);
    }

    public function test_un_directeur_ne_recoit_aucun_horaire_type(): void
    {
        $directeur = User::factory()->create();
        $this->rattacherADirection($directeur);

        Auth::login($directeur);

        (new ProfileController())->show();

        $this->assertSame(0, PlanningTemplate::where('user_id', $directeur->id)->count());
    }

    public function test_un_utilisateur_classique_recoit_un_horaire_type_par_defaut(): void
    {
        $user = User::factory()->create();

        Auth::login($user);

        (new ProfileController())->show();

        $this->assertSame(7, PlanningTemplate::where('user_id', $user->id)->count());
    }
}
