<?php

namespace Enterprise\Core\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * TenantScopedScope - A global scope that filters models by the current tenant hierarchy.
 * Enforces strict isolation at the database level.
 */
class TenantScopedScope implements Scope
{
    protected TenantContext $context;

    public function __construct(TenantContext $context)
    {
        $this->context = $context;
    }

    public function apply(Builder $builder, Model $model)
    {
        if (!$this->context->isSet()) {
            return;
        }

        $builder->where($model->getTable() . '.tenant_id', $this->context->getTenantId());

        if ($this->context->getOrganizationId()) {
            $builder->where($model->getTable() . '.organization_id', $this->context->getOrganizationId());
        }

        if ($this->context->getBranchId()) {
            $builder->where($model->getTable() . '.branch_id', $this->context->getBranchId());
        }

        if ($this->context->getLocationId()) {
            $builder->where($model->getTable() . '.location_id', $this->context->getLocationId());
        }

        if ($this->context->getDepartmentId()) {
            $builder->where($model->getTable() . '.department_id', $this->context->getDepartmentId());
        }
    }
}
