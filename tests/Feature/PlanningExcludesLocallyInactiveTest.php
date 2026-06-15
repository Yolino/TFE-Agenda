<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAgendaProfile;
use Tests\DatabaseTestCase;

class PlanningExcludesLocallyInactiveTest extends DatabaseTestCase
{
    public function test_le_scope_exclut_un_utilisateur_desactive_localement(): void
    {
        $actif       = User::factory()->create();
        $localInactif = User::factory()->create();
        $sansProfil  = User::factory()->create();

        UserAgendaProfile::create(['user_id' => $actif->id, 'actif' => true]);
        UserAgendaProfile::create(['user_id' => $localInactif->id, 'actif' => false]);

        $ids = User::activeInAgenda()->pluck('id');

        $this->assertContains($actif->id, $ids);
        $this->assertContains($sansProfil->id, $ids, 'Un utilisateur sans profil agenda reste actif par défaut.');
        $this->assertNotContains($localInactif->id, $ids);
    }

    public function test_le_scope_exclut_aussi_un_utilisateur_inactif_globalement(): void
    {
        $globalInactif = User::factory()->inactif()->create();

        UserAgendaProfile::create(['user_id' => $globalInactif->id, 'actif' => true]);

        $ids = User::activeInAgenda()->pluck('id');

        $this->assertNotContains($globalInactif->id, $ids);
    }
}
