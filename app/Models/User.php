<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'is_active', 'avatar',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // Role constants
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN_RADIOLOGI = 'admin_radiologi';
    const ROLE_RADIOGRAFER = 'radiografer';
    const ROLE_DOKTER_RADIOLOGI = 'dokter_radiologi';
    const ROLE_DIREKTUR = 'direktur';
    const ROLE_IT_SUPPORT = 'it_support';

    public static function roles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN_RADIOLOGI,
            self::ROLE_RADIOGRAFER,
            self::ROLE_DOKTER_RADIOLOGI,
            self::ROLE_DIREKTUR,
            self::ROLE_IT_SUPPORT,
        ];
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_ADMIN_RADIOLOGI => 'Admin Radiologi',
            self::ROLE_RADIOGRAFER => 'Radiografer',
            self::ROLE_DOKTER_RADIOLOGI => 'Dokter Radiologi',
            self::ROLE_DIREKTUR => 'Direktur',
            self::ROLE_IT_SUPPORT => 'IT Support',
            default => ucfirst($this->role),
        };
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        return $this->role === $roles;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN_RADIOLOGI]);
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    public function radiographer()
    {
        return $this->hasOne(Radiographer::class);
    }

    public function auditTrails()
    {
        return $this->hasMany(AuditTrail::class);
    }
}
