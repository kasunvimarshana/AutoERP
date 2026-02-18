<?php

namespace Modules\IAM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'avatar',
        'phone',
        'timezone',
        'locale',
        'is_active',
        'is_verified',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
        'mfa_enabled',
        'mfa_secret',
        'mfa_backup_codes',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
        'mfa_backup_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'mfa_enabled' => 'boolean',
        'mfa_backup_codes' => 'encrypted:array',
        'password' => 'hashed',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_active', 'roles'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Tenant::class);
    }

    public function loginAttempts(): HasMany
    {
        return $this->hasMany(LoginAttempt::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return parent::hasPermissionTo($permission, $guardName);
    }

    public function hasRole($roles, ?string $guard = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return parent::hasRole($roles, $guard);
    }

    public function updateLastLogin(string $ipAddress): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ]);
    }

    public function enableMfa(string $secret): void
    {
        $this->update([
            'mfa_enabled' => true,
            'mfa_secret' => encrypt($secret),
        ]);
    }

    public function disableMfa(): void
    {
        $this->update([
            'mfa_enabled' => false,
            'mfa_secret' => null,
        ]);
    }

    public function getMfaSecret(): ?string
    {
        return $this->mfa_secret ? decrypt($this->mfa_secret) : null;
    }

    public function getBackupCodes(): array
    {
        return $this->mfa_backup_codes ?? [];
    }

    public function setBackupCodes(array $codes): void
    {
        $this->mfa_backup_codes = $codes;
    }
}
