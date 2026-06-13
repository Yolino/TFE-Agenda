<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
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

    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->when($userId !== null && $userId !== '', fn ($q) => $q->where('user_id', $userId));
    }

    public function scopeForAction(Builder $query, ?string $action): Builder
    {
        return $query->when($action, fn ($q) => $q->where('action', $action));
    }

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

    public function getIsBtiAttribute(): bool
    {
        return str_starts_with((string) $this->action, 'bti.');
    }

    public function getActionLabelAttribute(): string
    {
        return self::labelFor((string) $this->action);
    }


    public static function labelFor(string $action): string
    {
        $labels = [
            'conge.accepted'             => 'Congé accepté',
            'conge.refused'              => 'Congé refusé',
            'conge.cancelled'            => 'Congé annulé',
            'justificatif.created'       => 'Justificatif déposé',
            'planning.created_for_other' => 'Horaire ajouté',
            'planning.updated_for_other' => 'Horaire modifié',
            'planning.filled_for_other'  => 'Horaire rempli',
            'planning.deleted_for_other' => 'Horaire supprimé',
        ];

        if (isset($labels[$action])) {
            return $labels[$action];
        }

        if (str_starts_with($action, 'bti.')) {
            return self::btiLabelFor($action);
        }

        return $action;
    }

    private static function btiLabelFor(string $action): string
    {

        if (preg_match('/^bti\.user\.(.+)_changed$/', $action, $m)) {
            $relations = [
                'agence'      => "Changement d'agence",
                'departement' => 'Changement de département',
                'societe'     => 'Changement de société',
            ];

            return ($relations[$m[1]] ?? 'Changement de ' . $m[1]) . ' (BTI)';
        }


        $parts = explode('.', $action);

        $entities = [
            'users'        => 'Utilisateur',
            'agences'      => 'Agence',
            'departements' => 'Département',
            'societes'     => 'Société',
        ];
        $events = [
            'created' => 'création',
            'updated' => 'modification',
            'deleted' => 'suppression',
        ];

        $entity = $entities[$parts[1] ?? ''] ?? ($parts[1] ?? '');
        $event  = $events[$parts[2] ?? ''] ?? ($parts[2] ?? '');

        return trim("{$entity} — {$event}") . ' (BTI)';
    }

    public function getSubjectLabelAttribute(): ?string
    {
        if (! $this->subject_type) {
            return null;
        }

        return class_basename($this->subject_type) . ($this->subject_id ? " #{$this->subject_id}" : '');
    }
}
