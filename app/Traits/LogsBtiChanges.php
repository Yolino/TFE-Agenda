<?php

namespace App\Traits;

use App\Services\ActivityLogger;

/**
 * Journalise AUTOMATIQUEMENT toute création / modification / suppression
 * d'un modèle vivant sur la base globale BTI (connexion partagée, donc
 * particulièrement sensible).
 *
 * Principe DRY : aucune ligne à disséminer dans le code métier. On branche
 * les événements Eloquent du modèle ; il suffit d'ajouter
 * `use \App\Traits\LogsBtiChanges;` sur chaque modèle BTI.
 *
 * Les actions sont préfixées par "bti." (ex : bti.users.updated) afin que
 * le visualiseur puisse les mettre en évidence.
 */
trait LogsBtiChanges
{
    /** Booté automatiquement par Eloquent (convention boot<NomDuTrait>). */
    public static function bootLogsBtiChanges(): void
    {
        static::created(function ($model) {
            ActivityLogger::record(
                $model->btiLogAction('created'),
                $model,
                ['attributs' => $model->btiLoggableAttributes($model->getAttributes())],
                'Création sur la base BTI'
            );
        });

        static::updated(function ($model) {
            // On ne loggue que s'il y a un réel changement métier (hors timestamps).
            $changes = $model->btiLoggableAttributes($model->getChanges());

            if (empty($changes)) {
                return;
            }

            ActivityLogger::record(
                $model->btiLogAction('updated'),
                $model,
                [
                    'avant'   => array_intersect_key(
                        $model->btiLoggableAttributes($model->getOriginal()),
                        $changes
                    ),
                    'apres'   => $changes,
                ],
                'Modification sur la base BTI'
            );
        });

        static::deleted(function ($model) {
            ActivityLogger::record(
                $model->btiLogAction('deleted'),
                $model,
                ['attributs' => $model->btiLoggableAttributes($model->getOriginal())],
                'Suppression sur la base BTI'
            );
        });
    }

    /** Nom d'action normalisé : bti.<table>.<événement> (le préfixe sert au surlignage). */
    protected function btiLogAction(string $event): string
    {
        return 'bti.' . $this->getTable() . '.' . $event;
    }

    /**
     * Prépare les attributs pour la journalisation :
     *  - retire les timestamps (bruit),
     *  - masque les champs cachés du modèle (mot de passe, remember_token...).
     */
    protected function btiLoggableAttributes(array $attributes): array
    {
        // Retirés purement et simplement : timestamps + jeton de session
        // (remember_token change à chaque login « se souvenir de moi » = bruit).
        unset($attributes['created_at'], $attributes['updated_at'], $attributes['remember_token']);

        // Les autres champs cachés (mot de passe...) sont conservés mais MASQUÉS :
        // on veut savoir qu'un mot de passe a changé, sans en exposer la valeur.
        foreach ($this->getHidden() as $hidden) {
            if ($hidden === 'remember_token') {
                continue;
            }
            if (array_key_exists($hidden, $attributes)) {
                $attributes[$hidden] = '••••••';
            }
        }

        return $attributes;
    }
}
