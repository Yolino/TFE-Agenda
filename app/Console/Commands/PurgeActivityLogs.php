<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class PurgeActivityLogs extends Command
{
    protected $signature = 'logs:purge-metier
                            {--months=6 : Âge maximal des logs à conserver (en mois)}
                            {--dry-run : Affiche le nombre de logs concernés sans rien supprimer}';

    protected $description = 'Supprime les logs métiers plus anciens que la rétention (6 mois par défaut)';

    public function handle(): int
    {
        $months = max(1, (int) $this->option('months'));
        $cutoff = now()->subMonths($months);

        $query = ActivityLog::where('created_at', '<', $cutoff);
        $count = $query->count();

        if ($count === 0) {
            $this->info("✓ Aucun log métier antérieur au {$cutoff->format('d/m/Y')} à purger.");
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("[dry-run] {$count} log(s) métier seraient supprimés (antérieurs au {$cutoff->format('d/m/Y')}).");
            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("✓ {$deleted} log(s) métier supprimé(s) (rétention : {$months} mois).");

        return self::SUCCESS;
    }
}
