<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Warehouse\Application\Contracts\WarehouseServiceInterface;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Events\WarehouseCreated;
use Modules\Warehouse\Domain\Events\WarehouseUpdated;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;

class WarehouseService implements WarehouseServiceInterface
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouseRepository,
    ) {}

    public function getWarehouse(string $tenantId, string $id): Warehouse
    {
        $warehouse = $this->warehouseRepository->findById($tenantId, $id);
        if ($warehouse === null) {
            throw new NotFoundException('Warehouse', $id);
        }
        return $warehouse;
    }

    public function getAllWarehouses(string $tenantId): array
    {
        return $this->warehouseRepository->findAll($tenantId);
    }

    public function createWarehouse(string $tenantId, array $data): Warehouse
    {
        return DB::transaction(function () use ($tenantId, $data): Warehouse {
            $now = now();
            $warehouse = new Warehouse(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                name: $data['name'],
                code: $data['code'],
                address: $data['address'] ?? null,
                isActive: (bool) ($data['is_active'] ?? true),
                createdAt: $now,
                updatedAt: $now,
            );
            $this->warehouseRepository->save($warehouse);
            Event::dispatch(new WarehouseCreated($warehouse));
            return $warehouse;
        });
    }

    public function updateWarehouse(string $tenantId, string $id, array $data): Warehouse
    {
        return DB::transaction(function () use ($tenantId, $id, $data): Warehouse {
            $existing = $this->getWarehouse($tenantId, $id);
            $warehouse = new Warehouse(
                id: $existing->id,
                tenantId: $existing->tenantId,
                name: $data['name'] ?? $existing->name,
                code: $data['code'] ?? $existing->code,
                address: array_key_exists('address', $data) ? $data['address'] : $existing->address,
                isActive: isset($data['is_active']) ? (bool) $data['is_active'] : $existing->isActive,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );
            $this->warehouseRepository->save($warehouse);
            Event::dispatch(new WarehouseUpdated($warehouse));
            return $warehouse;
        });
    }

    public function deleteWarehouse(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getWarehouse($tenantId, $id);
            $this->warehouseRepository->delete($tenantId, $id);
        });
    }
}
