<?php

declare(strict_types=1);

namespace Modules\IAM\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Core\Traits\LogsActivity;
use Modules\IAM\Database\Factories\PermissionFactory;

/**
 * Permission Model
 *
 * Represents a system permission that can be assigned to roles or users.
 * Uses native Laravel Eloquent - no external packages.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $module
 * @property string|null $resource
 * @property string|null $action
 * @property string|null $description
 * @property array|null $metadata
 * @property bool $is_active
 */
class Permission extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'module',
        'resource',
        'action',
        'description',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all roles that have this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.models.role', 'App\Models\Role'),
            'permission_role',
            'permission_id',
            'role_id'
        )->withTimestamps();
    }

    /**
     * Get all users that have this permission directly.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model', 'App\Models\User'),
            'user_permissions',
            'permission_id',
            'user_id'
        )->withPivot('type')
            ->withTimestamps();
    }

    /**
     * Scope to filter only active permissions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by module.
     */
    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * Scope to filter by resource.
     */
    public function scopeForResource(Builder $query, string $resource): Builder
    {
        return $query->where('resource', $resource);
    }

    /**
     * Scope to filter by action.
     */
    public function scopeForAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Get the full permission identifier (module.resource.action).
     */
    public function getFullIdentifierAttribute(): string
    {
        $parts = array_filter([
            $this->module,
            $this->resource,
            $this->action,
        ]);

        return implode('.', $parts) ?: $this->slug;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PermissionFactory
    {
        return PermissionFactory::new();
    }
}
