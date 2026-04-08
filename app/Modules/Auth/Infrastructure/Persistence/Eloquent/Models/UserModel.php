<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class UserModel extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasAudit, HasTenant, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'email',
        'password',
        'remember_token',
        'status',
        'email_verified_at',
        'phone',
        'avatar_path',
        'preferences',
        'locale',
        'timezone',
        'last_login_at',
        'last_login_ip',
        'two_factor_enabled',
        'two_factor_secret',
        'metadata',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected $casts = [
        'id'                 => 'int',
        'tenant_id'          => 'int',
        'preferences'        => 'array',
        'metadata'           => 'array',
        'email_verified_at'  => 'datetime',
        'last_login_at'      => 'datetime',
        'two_factor_enabled' => 'boolean',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'deleted_at'         => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'role_user', 'user_id', 'role_id')
            ->withTimestamps()
            ->withPivot(['tenant_id', 'assigned_at', 'expires_at']);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    // ── Permission helpers ────────────────────────────────────────────────────

    /**
     * Check whether this user has a role with the given slug.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles->contains('slug', $roleSlug);
    }

    /**
     * Check whether any of this user's roles include the given permission slug.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        foreach ($this->roles as $role) {
            if ($role->permissions->contains('slug', $permissionSlug)) {
                return true;
            }
        }

        return false;
    }
}
