<?php

namespace App\Console\Commands;

use App\Models\DemandeConge;
use App\Models\User;
use Illuminate\Console\Command;

class AuditCongeDecisions extends Command
{
    protected $signature = 'conges:audit-decisions
                            {--fix : Met à jour les décisions orphelines en associant un admin}
                            {--admin-id= : ID de l\'admin à utiliser comme décideur par défaut (optionnel)}';

    protected $description = 'Liste les demandes de congés acceptées/refusées sans décideur, et les corrige si --fix';

    public function handle(): int
    {
        $orphelins = DemandeConge::whereIn('status', ['acceptee', 'refusee'])
            ->whereNull('decided_by')
            ->with('user')
            ->orderBy('id')
            ->get();

        if ($orphelins->isEmpty()) {
            $this->info('✓ Aucune demande acceptée/refusée sans décideur.');
            $this->listSample();
            return self::SUCCESS;
        }

        $this->warn("Trouvé {$orphelins->count()} demande(s) acceptée(s)/refusée(s) sans decided_by :");
        $this->table(
            ['ID', 'User', 'Type', 'Du', 'Au', 'Statut', 'decided_by', 'decided_at'],
            $orphelins->map(fn ($c) => [
                $c->id,
                $c->user ? $c->user->firstname . ' ' . $c->user->name : "(user {$c->user_id})",
                $c->type,
                $c->start_date,
                $c->end_date,
                $c->status,
                $c->decided_by ?? 'NULL',
                $c->decided_at ?? 'NULL',
            ])->toArray()
        );

        if (!$this->option('fix')) {
            $this->comment('→ Relance avec --fix pour les corriger.');
            return self::SUCCESS;
        }

        $adminId = $this->option('admin-id');

        if ($adminId) {
            $admin = User::find($adminId);
            if (!$admin) {
                $this->error("Admin {$adminId} introuvable.");
                return self::FAILURE;
            }
        } else {
            $admin = User::where('role', 'A')->orderBy('id')->first();
            if (!$admin) {
                $this->error('Aucun admin trouvé en base. Fournis --admin-id=X.');
                return self::FAILURE;
            }
            $this->line("Admin par défaut sélectionné : #{$admin->id} — {$admin->firstname} {$admin->name}");
        }

        if (!$this->confirm("Associer ces {$orphelins->count()} décisions à {$admin->firstname} {$admin->name} ?", true)) {
            $this->comment('Annulé.');
            return self::SUCCESS;
        }

        foreach ($orphelins as $conge) {
            $conge->update([
                'decided_by' => $admin->id,
                'decided_at' => $conge->decided_at ?? $conge->updated_at ?? now(),
            ]);
        }

        $this->info("✓ {$orphelins->count()} décision(s) corrigée(s).");

        return self::SUCCESS;
    }

    private function listSample(): void
    {
        $sample = DemandeConge::whereIn('status', ['acceptee', 'refusee'])
            ->with('decidedBy')
            ->latest()
            ->take(5)
            ->get();

        if ($sample->isEmpty()) {
            $this->line('Aucune demande acceptée/refusée pour l\'instant.');
            return;
        }

        $this->line('');
        $this->line('5 dernières décisions :');
        $this->table(
            ['ID', 'Statut', 'Décideur', 'decided_at'],
            $sample->map(fn ($c) => [
                $c->id,
                $c->status,
                $c->decidedBy ? "{$c->decidedBy->firstname} {$c->decidedBy->name}" : '—',
                $c->decided_at,
            ])->toArray()
        );
    }
}
