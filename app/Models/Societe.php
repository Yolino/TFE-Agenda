<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Societe extends Model
{
    protected $connection = 'bti';

    protected $table = 'societes';

    public $timestamps = true;

    protected $guarded = [];

    public const BARA_SOCIETE_IDS = [3, 5, 6, 7, 8];

    public function agences(): HasMany
    {
        return $this->hasMany(Agence::class, 'societe_id');
    }

    public function isBara(): bool
    {
        return in_array((int) $this->id, self::BARA_SOCIETE_IDS, true);
    }

    public function getDisplayAliasAttribute(): string
    {
        return $this->isBara() ? 'BARA' : (string) $this->alias;
    }
}
