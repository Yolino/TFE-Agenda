<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Planning extends Model
{
    use HasFactory;

    protected $table = 'plannings';

    public const STATUS_MAP = [
        'bureau' => 1,
        'tele_travail' => 2,
        'conge' => 3,
        'recup' => 4,
        'css' => 5,
        'indisponible' => 6,
        'neant' => 7,
        'maladie' => 8,
        'jour_ferie' => 9,
    ];

    protected $fillable = [
        'user_id',
        'date',
        'start_time_morning',
        'end_time_morning',
        'start_time_afternoon',
        'end_time_afternoon',
        'actual_start_time_morning',
        'actual_end_time_morning',
        'actual_start_time_afternoon',
        'actual_end_time_afternoon',
        'status_id',
        'is_completed',
        'demande_conge_id',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function demandeConge(): BelongsTo
    {
        return $this->belongsTo(DemandeConge::class);
    }

    // Legacy compatibility with the previous API payload field names.
    public function getStatusAttribute(): ?string
    {
        return array_search($this->status_id, self::STATUS_MAP, true) ?: null;
    }

    public function setStatusAttribute(?string $status): void
    {
        $this->attributes['status_id'] = $status !== null ? (self::STATUS_MAP[$status] ?? null) : null;
    }

    public function getStartTimeAttribute(): ?string
    {
        return $this->start_time_morning;
    }

    public function setStartTimeAttribute(?string $value): void
    {
        $this->attributes['start_time_morning'] = $value;
    }

    public function getEndTimeAttribute(): ?string
    {
        return $this->end_time_morning;
    }

    public function setEndTimeAttribute(?string $value): void
    {
        $this->attributes['end_time_morning'] = $value;
    }
}
