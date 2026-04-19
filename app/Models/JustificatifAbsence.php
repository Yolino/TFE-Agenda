<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JustificatifAbsence extends Model
{
    use HasFactory;

    protected $table = 'justificatif_absence';

    protected $fillable = ['user_id', 'start_date', 'end_date', 'nb_jours', 'certificat_medical'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
