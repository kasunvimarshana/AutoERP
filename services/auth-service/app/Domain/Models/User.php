<?php

namespace App\Domain\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasUuids, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'id',
        'tenant_id',
        'org_id',
        'name',
        'email',
        'password',
        'phone',
        'timezone',
        'locale',
        'is_active',
        'metadata',
        'last_login_at',
        'last_login_ip',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'is_active'         => 'boolean',
        'metadata'          => 'array',
        'password'          => 'hashed',
    ];

    /**
     * Tenant relationship.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Organization relationship.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * Device tokens (for per-device SSO tracking).
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class, 'user_id');
    }

    /**
     * Audit logs.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    /**
     * Scope to active users only.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to a specific tenant.
     */
    public function scopeForTenant(\Illuminate\Database\Eloquent\Builder $query, string $tenantId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Guard name for Spatie permissions – use the api guard by default.
     */
    protected $guard_name = 'api';

    /**
     * Get the team id for permission/role scoping (tenant isolation).
     */
    public function getPermissionTeamId(): ?string
    {
        return $this->tenant_id;
    }

    /**
     * Create a Passport token with additional tenant claims embedded.
     */
    public function createTenantToken(string $name, array $scopes = [], array $extraClaims = []): \Laravel\Passport\PersonalAccessTokenResult
    {
        // Additional claims are stored as scopes with a claims: prefix
        $allScopes = $scopes;
        foreach ($extraClaims as $key => $value) {
            $allScopes[] = "claims:{$key}:{$value}";
        }

        return $this->createToken($name, $allScopes);
    }

    /**
     * Mark the user as having just logged in.
     */
    public function recordLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }
}
