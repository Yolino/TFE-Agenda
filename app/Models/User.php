<?php

namespace App\Models;

use App\Notifications\NewAccountSetPassword;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
        $default = config('database.default');
        $localDb = config("database.connections.{$default}.database");

        return $this->belongsToMany(
            Agence::class,
            "{$localDb}.agences_users",
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

    public function plannings(): HasMany
    {
        return $this->hasMany(Planning::class);
    }

    public function is_admin(): bool
    {
        return str_contains((string) $this->acces_level, 'A');
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
