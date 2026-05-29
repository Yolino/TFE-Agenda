<?php

namespace App\Models;

use App\Traits\LogsBtiChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Agence extends Model
{
    use LogsBtiChanges;

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
            'pivot_a_u',
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

}
