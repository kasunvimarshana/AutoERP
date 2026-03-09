<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tenant Model
 *
 * Core multi-tenant entity. Each tenant has isolated data,
 * configurations, and its own set of users and permissions.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $domain
 * @property string $plan
 * @property string $status
 * @property array|null $settings
 */
class Tenant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'plan',
        'status',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the tenant's runtime configurations.
     */
    public function configurations(): HasMany
    {
        return $this->hasMany(TenantConfiguration::class, 'tenant_id');
    }

    /**
     * Get configurations for a specific group.
     */
    public function configurationsForGroup(string $group): HasMany
    {
        return $this->configurations()->where('config_group', $group);
    }

    /**
     * Check if this tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
