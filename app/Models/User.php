<?php

namespace App\Models;

use App\Notifications\NewAccountSetPassword;
use App\Traits\LogsBtiChanges;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, LogsBtiChanges;

    public const ROLE_ETUDIANT = 'ET';

    public const DEPARTEMENT_DIRECTION_LETTER = 'D';

protected $connection = 'bti';

    protected $table = 'users';

    protected $fillable = [
        'name',
        'firstname',
        'alias',
        'phone',
        'email',
        'password',
        'acces_level',
        'avatar',
        'theme',
        'actif',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'actif'             => 'boolean',
    ];

    public function profile(): HasOne
    {
        return $this->hasOne(UserAgendaProfile::class, 'user_id');
    }

    public function agences(): BelongsToMany
    {
        return $this->belongsToMany(
            Agence::class,
            'pivot_a_u',
            'user_id',
            'agence_id'
        );
    }

    public function departements(): BelongsToMany
    {
        return $this->belongsToMany(
            Departement::class,
            'departement_user',
            'user_id',
            'departement_id'
        );
    }

    public function planningTemplates(): HasMany
    {
        return $this->hasMany(PlanningTemplate::class);
    }

    protected function agencesPivotTable(): string
    {
        return 'pivot_a_u';
    }

    public function plannings(): HasMany
    {
        return $this->hasMany(Planning::class);
    }

    public function scopeDirection(Builder $query): Builder
    {
        return $query->where('actif', true)
            ->whereHas('departements', function (Builder $q) {
                $q->where('letter', self::DEPARTEMENT_DIRECTION_LETTER);
            });
    }

    public function is_admin(): bool
    {
        return (bool) ($this->profile?->is_admin);
    }

    public function is_directeur(): bool
    {
        return $this->departements->contains('letter', self::DEPARTEMENT_DIRECTION_LETTER);
    }

    public function canAccessLogs(): bool
    {
        return $this->is_admin() || $this->is_directeur();
    }

    public function canManageUsers(): bool
    {
        return $this->is_admin() || $this->is_directeur();
    }

    public function canManageConges(): bool
    {
        return $this->is_directeur();
    }

    public function canEditGlobalPlanning(): bool
    {
        return $this->is_admin() || $this->is_directeur();
    }

    public function hasPersonalAgenda(): bool
    {
        return ! $this->is_directeur();
    }

    public function homeRoute(): string
    {
        return $this->is_directeur() ? 'planning' : 'mon-planning.index';
    }

    public function is_etudiant(): bool
    {
        return $this->acces_level === self::ROLE_ETUDIANT;
    }

    public function getFonctionAttribute(): ?string
    {
        return $this->departements->first()?->nom;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));
    }

    public function sendNewAccountNotification(string $token): void
    {
        $this->notify(new NewAccountSetPassword($token));
    }
}
