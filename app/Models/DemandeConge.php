<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandeConge extends Model
{
    use HasFactory;

    protected $table = 'demande_conge';

    protected $fillable = ['user_id', 'date', 'type', 'nb_jours', 'start_date', 'end_date', 'status'];

    /**
     * Relation vers l'utilisateur associé.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
