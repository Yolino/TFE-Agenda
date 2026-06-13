<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandeConge extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'demande_conge';

    public const TYPE_LABELS = [
        'recup'  => 'Récupération',
        'conge'  => 'Congé',
        'css'    => 'Congé sans solde',
        'visite' => 'Visite médicale',
        'autre'  => 'Autre',
    ];

    protected $fillable = ['user_id', 'date', 'type', 'nb_jours', 'start_date', 'end_date', 'status', 'decided_by', 'decided_at', 'cancelled_at', 'cancelled_by'];

    protected $casts = [
        'decided_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'nb_jours' => 'float',
    ];

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

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? (string) $this->type;
    }

    public function getFormattedJoursAttribute(): string
    {
        $n = (float) $this->nb_jours;
        $clean = rtrim(rtrim(number_format($n, 1, ',', ' '), '0'), ',');

        return $clean . ' ' . ($n > 1 ? 'jours' : 'jour');
    }
}
