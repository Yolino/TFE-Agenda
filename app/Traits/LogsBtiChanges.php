<?php

namespace App\Traits;

use App\Services\ActivityLogger;

trait LogsBtiChanges
{
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

    protected function btiLogAction(string $event): string
    {
        return 'bti.' . $this->getTable() . '.' . $event;
    }

    protected function btiLoggableAttributes(array $attributes): array
    {
        unset($attributes['created_at'], $attributes['updated_at'], $attributes['remember_token']);

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
