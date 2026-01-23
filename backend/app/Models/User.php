<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, LogsActivity, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'branch_id',
        'name',
        'email',
        'password',
        'role',
        'status',
        'mfa_enabled',
        'email_verified_at',
        'password_changed_at',
        'last_login_at',
        'failed_login_attempts',
        'locked_until',
        'settings',
        'security_settings',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'mfa_enabled' => 'boolean',
            'failed_login_attempts' => 'integer',
            'settings' => 'array',
            'security_settings' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(\App\Modules\TenantManagement\Models\Tenant::class);
    }

    public function vendor()
    {
        return $this->belongsTo(\App\Modules\TenantManagement\Models\Tenant::class, 'vendor_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Modules\TenantManagement\Models\Tenant::class, 'branch_id');
    }

    public function mfaSecrets()
    {
        return $this->hasMany(\App\Modules\AuthManagement\Models\MfaSecret::class);
    }

    public function sessions()
    {
        return $this->hasMany(\App\Modules\AuthManagement\Models\UserSession::class);
    }

    public function securityAuditLogs()
    {
        return $this->hasMany(\App\Modules\AuthManagement\Models\SecurityAuditLog::class);
    }

    // Scopes
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function isMfaEnabled(): bool
    {
        return $this->mfa_enabled;
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function belongsToTenant(int $tenantId): bool
    {
        return $this->tenant_id === $tenantId;
    }

    public function belongsToVendor(int $vendorId): bool
    {
        return $this->vendor_id === $vendorId;
    }

    public function belongsToBranch(int $branchId): bool
    {
        return $this->branch_id === $branchId;
    }

    public function hasPermissionTo($permission): bool
    {
        if ($this->role === 'super_admin') {
            return true;
        }
        return parent::hasPermissionTo($permission);
    }

    public function canAccessTenant(?int $tenantId): bool
    {
        // Super admin can access all tenants
        if ($this->role === 'super_admin') {
            return true;
        }

        // Check tenant access
        return $this->tenant_id === $tenantId;
    }

    public function canAccessResource(int $tenantId, ?int $vendorId = null, ?int $branchId = null): bool
    {
        // Check tenant
        if (!$this->canAccessTenant($tenantId)) {
            return false;
        }

        // Check vendor if specified
        if ($vendorId && $this->vendor_id && $this->vendor_id !== $vendorId) {
            return false;
        }

        // Check branch if specified
        if ($branchId && $this->branch_id && $this->branch_id !== $branchId) {
            return false;
        }

        return true;
    }
}
