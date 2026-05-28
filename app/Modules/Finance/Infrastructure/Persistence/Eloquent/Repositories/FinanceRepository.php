<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;

abstract class FinanceRepository extends EloquentRepository
{
    /** @var array<string, bool> */
    private static array $tenantColumnCache = [];

    public function query(array $with = []): Builder
    {
        $query = parent::query($with);

        $tenantId = $this->resolveCurrentTenantId();
        if ($tenantId === null) {
            return $query;
        }

        $table = $this->model->getTable();
        $hasTenantColumn = self::$tenantColumnCache[$table] ??= Schema::hasColumn($table, 'tenant_id');
        if (! $hasTenantColumn) {
            return $query;
        }

        return $query->where($table . '.tenant_id', $tenantId);
    }

    private function resolveCurrentTenantId(): ?int
    {
        if (! app()->bound(CurrentTenantContextAccessorInterface::class)) {
            return null;
        }

        $accessor = app(CurrentTenantContextAccessorInterface::class);

        return $accessor->currentTenantId();
    }
}
