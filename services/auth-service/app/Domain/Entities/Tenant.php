<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tenant Entity
 *
 * Represents a tenant (organization) in the multi-tenant system.
 */
class Tenant extends Model
{
    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'is_active',
        'settings',
        'plan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get all users for this tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
