<?php

namespace Tests\Feature;

use App\Models\DemandeConge;
use App\Models\JustificatifAbsence;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

class DirecteurDocumentsAccessTest extends DatabaseTestCase
{
    private function directeur(): User
    {
        $user = User::factory()->create();

        DB::connection('bti')->table('departements')->insertOrIgnore([
            ['id' => 1, 'letter' => User::DEPARTEMENT_DIRECTION_LETTER, 'nom' => 'Direction'],
        ]);
        DB::connection('bti')->table('departement_user')->insert([
            'user_id'        => $user->id,
            'departement_id' => 1,
        ]);

        return $user;
    }

    private function justificatif(User $owner): JustificatifAbsence
    {
        Storage::disk('medical')->put('certs/cert.pdf', 'PDF');

        return JustificatifAbsence::create([
            'user_id'            => $owner->id,
            'start_date'         => '2026-01-01',
            'end_date'           => '2026-01-03',
            'nb_jours'           => 3,
            'certificat_medical' => 'certs/cert.pdf',
        ]);
    }

    public function test_un_directeur_peut_consulter_le_certificat_d_un_collaborateur(): void
    {
        Storage::fake('medical');
        $justif = $this->justificatif(User::factory()->create());

        $this->actingAs($this->directeur())
            ->get(route('justificatif-absence.certificat', $justif->id))
            ->assertOk();
    }

    public function test_le_proprietaire_peut_consulter_son_certificat(): void
    {
        Storage::fake('medical');
        $owner = User::factory()->create();
        $justif = $this->justificatif($owner);

        $this->actingAs($owner)
            ->get(route('justificatif-absence.certificat', $justif->id))
            ->assertOk();
    }

    public function test_un_tiers_ne_peut_pas_consulter_un_certificat(): void
    {
        Storage::fake('medical');
        $justif = $this->justificatif(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get(route('justificatif-absence.certificat', $justif->id))
            ->assertForbidden();
    }

    public function test_un_tiers_ne_peut_pas_voir_le_pdf_d_un_conge(): void
    {
        $conge = DemandeConge::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs(User::factory()->create())
            ->get(route('mes-conges.pdf', $conge->id))
            ->assertForbidden();
    }

    public function test_un_directeur_peut_voir_le_pdf_d_un_conge(): void
    {
        $conge = DemandeConge::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($this->directeur())
            ->get(route('mes-conges.pdf', $conge->id))
            ->assertOk();
    }
}
