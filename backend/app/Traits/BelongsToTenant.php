<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the trait — auto-apply global scope and auto-fill tenant_id on creation.
     */
    public static function bootBelongsToTenant(): void
    {
        // Auto-fill tenant_id from the resolved tenant in the container
        static::creating(function ($model) {
            if (empty($model->tenant_id) && app()->bound('current_tenant')) {
                $tenant = app('current_tenant');
                if ($tenant instanceof Tenant) {
                    $model->tenant_id = $tenant->id;
                }
            }
        });

        // Apply global scope for all queries
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->bound('current_tenant')) {
                $tenant = app('current_tenant');
                if ($tenant instanceof Tenant) {
                    $builder->where(
                        $builder->getModel()->getTable() . '.tenant_id',
                        $tenant->id
                    );
                }
            }
        });
    }

    // ─── Relationship ─────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    /**
     * Bypass the tenant scope (useful for admin/system operations).
     */
    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }

    /**
     * Execute a callback without the tenant scope.
     */
    public static function withoutTenant(callable $callback): mixed
    {
        return $callback(static::withoutGlobalScope('tenant'));
    }
}
