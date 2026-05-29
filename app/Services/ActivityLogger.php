<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Service de journalisation MÉTIER — point d'entrée UNIQUE.
 *
 * Objectif DRY : tracer n'importe quelle action sensible en UNE ligne,
 * depuis n'importe où (contrôleur, composant Livewire, service, commande) :
 *
 *     ActivityLogger::record('conge.accepted', $demande, ['status' => 'acceptee']);
 *
 * Toute la logique (auteur courant, IP, user-agent, normalisation) est
 * concentrée ici ; aucun appelant ne la redéfinit.
 */
class ActivityLogger
{
    /**
     * Enregistre une action métier.
     *
     * @param  string      $action       Nature de l'action, ex : "user.deactivated".
     * @param  Model|null  $subject      Donnée concernée (le modèle visé).
     * @param  array       $properties   Données concernées (avant/après, contexte...).
     * @param  string|null $description  Phrase lisible pour l'humain (optionnel).
     */
    public function log(
        string $action,
        ?Model $subject = null,
        array $properties = [],
        ?string $description = null
    ): ?ActivityLog {
        $user = Auth::user();

        // La journalisation ne doit JAMAIS casser le flux métier appelant.
        // En cas d'échec d'écriture, on bascule sur le canal technique.
        try {
            return ActivityLog::create([
                'user_id'      => $user?->getKey(),
                'user_name'    => $user ? trim(($user->firstname ?? '') . ' ' . ($user->name ?? '')) : null,
                'action'       => $action,
                'description'  => $description,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id'   => $subject?->getKey(),
                'properties'   => $properties ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::channel('technique')->error('Échec écriture ActivityLog', [
                'action'  => $action,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Raccourci statique pour un appel en une ligne sans injection de dépendance.
     * Délègue à l'instance résolue par le conteneur (toujours la même logique).
     */
    public static function record(
        string $action,
        ?Model $subject = null,
        array $properties = [],
        ?string $description = null
    ): ?ActivityLog {
        return app(self::class)->log($action, $subject, $properties, $description);
    }
}
