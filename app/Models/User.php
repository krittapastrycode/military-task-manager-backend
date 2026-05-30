<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasUuids;

    protected $fillable = [
        'email',
        'password',
        'google_id',
        'name',
        'rank',
        'position',
        'unit',
        'role',
        'avatar_url',
        'phone',
        'is_active',
        'email_verified',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'email_verified' => 'boolean',
            'password' => 'hashed',
            'role' => 'array',
        ];
    }

    public function taskGroups(): HasMany
    {
        return $this->hasMany(TaskGroup::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function lineUser(): HasOne
    {
        return $this->hasOne(LineUser::class);
    }

    public function googleCalendarToken(): HasOne
    {
        return $this->hasOne(GoogleCalendarToken::class);
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function hasRole(string $role): bool
    {
        $roles = $this->role;
        if (is_string($roles)) {
            $decoded = json_decode($roles, true);
            $roles = is_array($decoded) ? $decoded : [$roles];
        }
        return in_array($role, (array) ($roles ?? []), true);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('admin') || $this->hasRole('commander');
    }
}
