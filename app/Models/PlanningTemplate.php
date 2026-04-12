<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanningTemplate extends Model
{
    use HasFactory;

    protected $table = 'planning_templates';

    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time_morning',
        'end_time_morning',
        'start_time_afternoon',
        'end_time_afternoon',
        'status_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusAttribute(): ?string
    {
        return array_search($this->status_id, Planning::STATUS_MAP, true) ?: null;
    }

    public function setStatusAttribute(?string $status): void
    {
        $this->attributes['status_id'] = $status !== null ? (Planning::STATUS_MAP[$status] ?? null) : null;
    }
}
