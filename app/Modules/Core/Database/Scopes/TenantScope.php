<?php

declare(strict_types=1);

namespace Modules\Core\Database\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\Core\Contracts\TenantExecutionContextInterface;

final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $executionContext = app(TenantExecutionContextInterface::class);
        $tenantId = $executionContext->tenantId();

        if ($tenantId !== null) {
            $builder->where($model->qualifyColumn('tenant_id'), $tenantId);

            return;
        }

        if ($executionContext->isControlPlane()) {
            return;
        }

        // Tenant-owned data is inaccessible without a trusted execution boundary.
        // A caller-provided tenant_id predicate is data, not authorization, and must
        // never be treated as permission to query another tenant.
        $builder->whereRaw('1 = 0');
    }
}
