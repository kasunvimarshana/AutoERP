<?php

// app/Modules/Core/Infrastructure/Scopes/TenantScope.php
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if ($tenantId = app('currentTenantId')) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }
}