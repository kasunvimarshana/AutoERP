<?php

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Tenant;
use Modules\Core\Scopes\TenantScope;

/**
 * Trait for models that belong to a tenant
 * Automatically applies tenant scoping to prevent cross-tenant data leaks
 */
trait BelongsToTenant
{
    /**
     * Boot the trait - automatically apply tenant scope
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(app(TenantScope::class));

        // Automatically set tenant_id when creating a model
        static::creating(function ($model) {
            if (!isset($model->tenant_id) && !$model->tenant_id) {
                $tenantContext = app(\Modules\Core\Services\TenantContext::class);
                $tenantId = $tenantContext->getTenantId();
                
                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }

    /**
     * Get the tenant that owns the model
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope a query to a specific tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to exclude tenant filtering
     */
    public function scopeForAllTenants($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
