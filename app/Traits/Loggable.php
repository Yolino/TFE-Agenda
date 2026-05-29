<?php

namespace App\Traits;

use App\Models\ActivityLog;
use App\Services\ActivityLogger;

/**
 * À ajouter sur n'importe quel modèle dont on veut tracer les actions :
 *
 *     class DemandeConge extends Model {
 *         use \App\Traits\Loggable;
 *     }
 *
 * Puis, en une ligne, depuis une instance :
 *
 *     $demande->logActivity('conge.accepted', ['status' => 'acceptee']);
 *
 * Le trait ne réimplémente rien : il délègue au service ActivityLogger
 * (source unique de vérité) en passant automatiquement $this comme sujet.
 */
trait Loggable
{
    public function logActivity(
        string $action,
        array $properties = [],
        ?string $description = null
    ): ?ActivityLog {
        return ActivityLogger::record($action, $this, $properties, $description);
    }
}
