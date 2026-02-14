<?php

namespace App\Models;

use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, TenantAware, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'branch_id',
        'name',
        'username',
        'email',
        'phone',
        'password',
        'is_active',
        'is_verified',
        'last_login_at',
        'settings',
        'metadata',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'last_login_at' => 'datetime',
            'settings' => 'array',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Organization\Models\Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Organization\Models\Branch::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\IAM\Models\Role::class, 'user_role');
    }

    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })
            ->exists();
    }

    public function assignRole($role): void
    {
        if (is_string($role)) {
            $role = \App\Modules\IAM\Models\Role::where('slug', $role)->firstOrFail();
        }

        if (!$this->hasRole($role->slug)) {
            $this->roles()->attach($role);
        }
    }

    public function removeRole($role): void
    {
        if (is_string($role)) {
            $role = \App\Modules\IAM\Models\Role::where('slug', $role)->firstOrFail();
        }

        $this->roles()->detach($role);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }
}

