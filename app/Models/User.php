<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Traits\HasPermissions;
use Modules\Core\Traits\Tenantable;

/**
 * User Model
 *
 * Application user with native RBAC and multi-tenancy support.
 * No external packages required beyond Laravel Sanctum for API authentication.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasPermissions, Tenantable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'permissions',
        'is_active',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    public function getCurrentTenantId(): ?int
    {
        return $this->tenant_id;
    }
}
