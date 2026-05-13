<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAgendaProfile extends Model
{
    protected $connection = 'mysql';

    protected $table = 'user_agenda_profiles';

    protected $fillable = [
        'user_id',
        'fixe',
        'remarque',
        'actif',
        'is_admin',
    ];

    protected $casts = [
        'actif'    => 'boolean',
        'is_admin' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
