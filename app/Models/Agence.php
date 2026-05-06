<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Agence extends Model
{
    protected $connection = 'bti';

    protected $table = 'agences';

    public $timestamps = true;

    protected $guarded = [];

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class, 'societe_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            $this->localPivotTable(),
            'agence_id',
            'user_id'
        );
    }

    public function getDisplayNameAttribute(): string
    {
        $societe = $this->societe;
        $societeAlias = $societe ? $societe->display_alias : '';

        return trim(($this->alias ?? '') . ' ' . $societeAlias);
    }

    protected function localPivotTable(): string
    {
        $default = config('database.default');
        $database = config("database.connections.{$default}.database");

        return "{$database}.agences_users";
    }
}
