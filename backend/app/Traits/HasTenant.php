<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/**
 * HasTenant Trait
 * 
 * This trait automatically:
 * 1. Adds a global scope to filter queries by tenant_id
 * 2. Sets tenant_id when creating new records
 * 3. Ensures strict tenant isolation across the application
 * 
 * Usage: Add this trait to any model that should be tenant-aware.
 */
trait HasTenant
{
    /**
     * Boot the HasTenant trait for a model.
     *
     * @return void
     */
    protected static function bootHasTenant(): void
    {
        // Add global scope for automatic tenant filtering
        static::addGlobalScope(new TenantScope());

        // Automatically set tenant_id when creating a new record
        static::creating(function (Model $model) {
            if (auth()->check() && !$model->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    /**
     * Get the tenant relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Check if model belongs to the current tenant
     *
     * @return bool
     */
    public function isOwnedByCurrentTenant(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return $this->tenant_id === auth()->user()->tenant_id;
    }

    /**
     * Scope a query to only include records for a specific tenant
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $tenantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
