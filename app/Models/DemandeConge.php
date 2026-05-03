<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandeConge extends Model
{
    use HasFactory;

    protected $table = 'demande_conge';

    protected $fillable = ['user_id', 'date', 'type', 'nb_jours', 'start_date', 'end_date', 'status', 'decided_by', 'decided_at', 'cancelled_at', 'cancelled_by'];

    protected $casts = [
        'decided_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'nb_jours' => 'float',
    ];

    /**
     * Relation vers l'utilisateur associé.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
