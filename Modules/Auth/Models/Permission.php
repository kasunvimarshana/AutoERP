<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * Permission Model
 *
 * RBAC Permission implementation
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $resource
 * @property string $action
 * @property array $metadata
 * @property bool $is_system
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Permission extends Model
{
    use HasFactory, HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'resource',
        'action',
        'metadata',
        'is_system',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_system' => 'boolean',
    ];

    /**
     * Get roles with this permission
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * Get users with this permission
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->withTimestamps();
    }

    /**
     * Scope by resource
     */
    public function scopeForResource($query, string $resource)
    {
        return $query->where('resource', $resource);
    }

    /**
     * Scope by action
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}
