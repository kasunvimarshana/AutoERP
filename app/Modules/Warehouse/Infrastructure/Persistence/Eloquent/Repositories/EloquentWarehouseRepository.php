<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

final class EloquentWarehouseRepository extends EloquentRepository implements WarehouseRepositoryInterface
{
    public function __construct(WarehouseModel $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code, int $tenantId): mixed
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('code', $code)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function findActiveByTenant(int $tenantId): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
