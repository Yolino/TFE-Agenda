<?php

namespace Tests\Feature;

use App\Livewire\AdminConges;
use App\Models\ActivityLog;
use App\Models\DemandeConge;
use App\Models\Planning;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Tests\DatabaseTestCase;

class AdminCongesApprovalTest extends DatabaseTestCase
{
    public function test_accepter_valide_la_demande_et_alimente_le_planning(): void
    {
        $user = User::factory()->create();
        $conge = DemandeConge::factory()->envoyee()->create([
            'user_id'    => $user->id,
            'type'       => 'conge',
            'start_date' => '2024-01-01',
            'end_date'   => '2024-01-03',
            'nb_jours'   => 3,
        ]);

        Auth::login($user);

        (new AdminConges())->accepter($conge->id, app(LeaveBalanceService::class));

        $conge->refresh();
        $this->assertSame('acceptee', $conge->status);
        $this->assertSame($user->id, (int) $conge->decided_by);
        $this->assertNotNull($conge->decided_at);

        $this->assertTrue(
            Planning::where('demande_conge_id', $conge->id)
                ->where('status_id', Planning::STATUS_MAP['conge'])
                ->exists()
        );

        $this->assertDatabaseHas('logs', [
            'action'       => 'conge.accepted',
            'subject_type' => DemandeConge::class,
            'subject_id'   => $conge->id,
            'user_id'      => $user->id,
        ], 'mysql');
    }

    public function test_refuser_rejette_la_demande_sans_toucher_au_planning(): void
    {
        $user = User::factory()->create();
        $conge = DemandeConge::factory()->envoyee()->create(['user_id' => $user->id]);

        Auth::login($user);

        (new AdminConges())->refuser($conge->id);

        $conge->refresh();
        $this->assertSame('refusee', $conge->status);
        $this->assertSame($user->id, (int) $conge->decided_by);
        $this->assertNotNull($conge->decided_at);

        $this->assertSame(0, Planning::where('demande_conge_id', $conge->id)->count());

        $this->assertDatabaseHas('logs', [
            'action'       => 'conge.refused',
            'subject_type' => DemandeConge::class,
            'subject_id'   => $conge->id,
            'user_id'      => $user->id,
        ], 'mysql');
    }

    public function test_une_demande_deja_tranchee_ne_peut_pas_etre_acceptee(): void
    {
        $user = User::factory()->create();
        $conge = DemandeConge::factory()->acceptee()->create(['user_id' => $user->id]);

        Auth::login($user);

        $this->expectException(ModelNotFoundException::class);

        (new AdminConges())->accepter($conge->id, app(LeaveBalanceService::class));
    }
}
