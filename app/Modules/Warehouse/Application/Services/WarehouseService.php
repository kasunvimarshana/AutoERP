<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\Warehouse\Application\Contracts\WarehouseServiceInterface;
use Modules\Warehouse\Domain\Contracts\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Domain\Events\WarehouseCreated;
use Modules\Warehouse\Domain\Exceptions\WarehouseNotFoundException;

class WarehouseService extends BaseService implements WarehouseServiceInterface
{
    public function __construct(WarehouseRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function handle(array $data): mixed
    {
        return $this->createWarehouse($data);
    }

    public function createWarehouse(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            $warehouse = $this->repository->create($data);
            $this->addEvent(new WarehouseCreated((int) ($warehouse->tenant_id ?? 0), $warehouse->id));
            $this->dispatchEvents();

            return $warehouse;
        });
    }

    public function updateWarehouse(string $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            $warehouse = $this->repository->find($id);
            if (! $warehouse) {
                throw new WarehouseNotFoundException($id);
            }

            return $this->repository->update($id, $data);
        });
    }
}
