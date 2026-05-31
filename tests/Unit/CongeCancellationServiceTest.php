<?php

namespace Tests\Unit;

use App\Models\DemandeConge;
use App\Models\Planning;
use App\Services\CongeCancellationService;
use Tests\DatabaseTestCase;

/**
 * L'annulation d'un congé doit : marquer la demande comme "annulee", tracer
 * l'auteur, ET supprimer en cascade les jours de planning qui lui sont liés —
 * sans toucher aux autres jours. C'est une opération transactionnelle sensible.
 */
class CongeCancellationServiceTest extends DatabaseTestCase
{
    private CongeCancellationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CongeCancellationService::class);
    }

    public function test_l_annulation_marque_la_demande_et_purge_le_planning_lie(): void
    {
        $conge = DemandeConge::factory()->acceptee()->create(['user_id' => 1]);

        Planning::factory()->count(3)->create([
            'user_id'          => 1,
            'demande_conge_id' => $conge->id,
            'status_id'        => Planning::STATUS_MAP['conge'],
        ]);

        // Jour de planning SANS lien : il ne doit pas être supprimé.
        Planning::factory()->create(['user_id' => 1, 'demande_conge_id' => null]);

        $this->service->cancel($conge, 77);

        $conge->refresh();
        $this->assertSame('annulee', $conge->status);
        $this->assertSame(77, (int) $conge->cancelled_by);
        $this->assertNotNull($conge->cancelled_at);

        $this->assertSame(0, Planning::where('demande_conge_id', $conge->id)->count());
        $this->assertSame(1, Planning::whereNull('demande_conge_id')->count());
    }

    public function test_l_annulation_est_idempotente_si_la_demande_est_deja_annulee(): void
    {
        $conge = DemandeConge::factory()->annulee()->create([
            'user_id'      => 1,
            'cancelled_by' => 5,
        ]);

        Planning::factory()->create([
            'user_id'          => 1,
            'demande_conge_id' => $conge->id,
        ]);

        $this->service->cancel($conge, 77);

        $conge->refresh();
        // Rien ne change : ni l'auteur initial de l'annulation, ni le planning.
        $this->assertSame(5, (int) $conge->cancelled_by);
        $this->assertSame(1, Planning::where('demande_conge_id', $conge->id)->count());
    }

    public function test_annulation_depuis_un_planning_sans_demande_liee_retourne_null(): void
    {
        $planning = Planning::factory()->create(['user_id' => 1, 'demande_conge_id' => null]);

        $this->assertNull($this->service->cancelFromPlanning($planning, 1));
    }

    public function test_annulation_depuis_un_planning_annule_la_demande_liee(): void
    {
        $conge = DemandeConge::factory()->acceptee()->create(['user_id' => 1]);

        $planning = Planning::factory()->create([
            'user_id'          => 1,
            'demande_conge_id' => $conge->id,
        ]);

        $result = $this->service->cancelFromPlanning($planning, 7);

        $this->assertNotNull($result);
        $this->assertSame('annulee', $result->status);
        $this->assertSame(7, (int) $result->cancelled_by);
    }
}
