<?php

namespace Shared\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * Hierarchical Tenant Isolation Trait
 * Automatically scopes all queries to Tenant -> Organisation -> Branch -> Location -> Department.
 */
trait HasTenantScope
{
    public static function bootHasTenantScope(): void
    {
        static::creating(function (Model $model) {
            // Automatically set context on creation
            $model->tenant_id = $model->tenant_id ?? Config::get('auth.tenant_id');
            $model->organisation_id = $model->organisation_id ?? Config::get('auth.organisation_id');
            $model->branch_id = $model->branch_id ?? Config::get('auth.branch_id');
            $model->location_id = $model->location_id ?? Config::get('auth.location_id');
            $model->department_id = $model->department_id ?? Config::get('auth.department_id');
        });

        static::addGlobalScope('tenant_scope', function (Builder $builder) {
            $builder->where('tenant_id', Config::get('auth.tenant_id'));

            // Hierarchical narrowing if set in context
            if ($orgId = Config::get('auth.organisation_id')) {
                $builder->where('organisation_id', $orgId);
            }
            if ($branchId = Config::get('auth.branch_id')) {
                $builder->where('branch_id', $branchId);
            }
            if ($locationId = Config::get('auth.location_id')) {
                $builder->where('location_id', $locationId);
            }
            if ($deptId = Config::get('auth.department_id')) {
                $builder->where('department_id', $deptId);
            }
        });
    }

    /**
     * Helper to bypass scope for administrative tasks (use with extreme caution!)
     */
    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope('tenant_scope');
    }
}
