<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Log MÉTIER : trace une action sensible (qui / quoi / quand / sur quoi).
 *
 * Rétention : 6 mois glissants, assurée par la commande `logs:purge-metier`
 * planifiée dans App\Console\Kernel.
 */
class ActivityLog extends Model
{
    /** Données locales => même connexion que user_agenda_profiles. */
    protected $connection = 'mysql';

    protected $table = 'logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | SCOPES DE FILTRAGE (réutilisés tels quels par le composant Livewire)
    |--------------------------------------------------------------------------
    | Centraliser la logique de filtrage ici évite de la dupliquer dans chaque
    | écran qui consommerait les logs (principe DRY).
    */

    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->when($userId !== null && $userId !== '', fn ($q) => $q->where('user_id', $userId));
    }

    public function scopeForAction(Builder $query, ?string $action): Builder
    {
        return $query->when($action, fn ($q) => $q->where('action', $action));
    }

    /** Filtre sur une plage de dates (bornes optionnelles, format Y-m-d). */
    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function ($q) use ($term) {
            $q->where(function ($sub) use ($term) {
                $sub->where('description', 'like', "%{$term}%")
                    ->orWhere('user_name', 'like', "%{$term}%")
                    ->orWhere('action', 'like', "%{$term}%");
            });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | PRÉSENTATION (centralisée pour la vue)
    |--------------------------------------------------------------------------
    */

    /**
     * Couleur du badge DaisyUI déduite du verbe de l'action.
     * Ex : "conge.accepted" => badge-success ; "user.deleted" => badge-error.
     * Un seul endroit décide des couleurs => cohérence visuelle garantie.
     */
    public function getBadgeClassAttribute(): string
    {
        $verb = str_contains($this->action, '.')
            ? \Illuminate\Support\Str::afterLast($this->action, '.')
            : $this->action;

        return match (true) {
            str_contains($verb, 'creat'), str_contains($verb, 'add'),
            in_array($verb, ['accepted', 'approved', 'login', 'restored']) => 'badge-success',

            str_contains($verb, 'updat'), str_contains($verb, 'edit'),
            str_contains($verb, 'chang'),
            in_array($verb, ['sent']) => 'badge-info',

            str_contains($verb, 'delet'), str_contains($verb, 'remov'),
            in_array($verb, ['refused', 'rejected', 'failed']) => 'badge-error',

            in_array($verb, ['cancelled', 'canceled', 'logout']) => 'badge-warning',

            str_contains($verb, 'export'), str_contains($verb, 'download') => 'badge-accent',

            default => 'badge-neutral',
        };
    }

    /**
     * Vrai si le log concerne une écriture sur la base globale BTI
     * (action préfixée "bti."). Utilisé par la vue pour la mise en évidence.
     */
    public function getIsBtiAttribute(): bool
    {
        return str_starts_with((string) $this->action, 'bti.');
    }

    /** Libellé court et lisible du sujet concerné. */
    public function getSubjectLabelAttribute(): ?string
    {
        if (! $this->subject_type) {
            return null;
        }

        return class_basename($this->subject_type) . ($this->subject_id ? " #{$this->subject_id}" : '');
    }
}
