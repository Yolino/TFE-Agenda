<?php

namespace App\Models;

use App\Traits\LogsBtiChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Departement extends Model
{
    use LogsBtiChanges;

    protected $connection = 'bti';

    protected $table = 'departements';

    public $timestamps = true;

    protected $guarded = [];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'departement_user',
            'departement_id',
            'user_id'
        );
    }
}
