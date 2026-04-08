<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Warehouse\Application\Contracts\WarehouseLocationServiceInterface;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;
use Modules\Warehouse\Domain\Events\WarehouseLocationCreated;
use Modules\Warehouse\Domain\Events\WarehouseLocationUpdated;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;

class WarehouseLocationService implements WarehouseLocationServiceInterface
{
    public function __construct(
        private readonly WarehouseLocationRepositoryInterface $locationRepository,
    ) {}

    public function getLocation(string $tenantId, string $id): WarehouseLocation
    {
        $location = $this->locationRepository->findById($tenantId, $id);
        if ($location === null) {
            throw new NotFoundException('WarehouseLocation', $id);
        }
        return $location;
    }

    public function getTree(string $tenantId, string $warehouseId): array
    {
        $locations = $this->locationRepository->findByWarehouse($tenantId, $warehouseId);

        /** @var array<string, array<string, mixed>> $map */
        $map = [];
        foreach ($locations as $location) {
            $map[$location->id] = [
                'id'        => $location->id,
                'name'      => $location->name,
                'code'      => $location->code,
                'path'      => $location->path,
                'level'     => $location->level,
                'type'      => $location->type,
                'is_active' => $location->isActive,
                'children'  => [],
                '_parentId' => $location->parentId,
            ];
        }

        $roots = [];
        foreach ($map as $id => &$node) {
            $parentId = $node['_parentId'];
            unset($node['_parentId']);
            if ($parentId === null) {
                $roots[] = &$node;
            } elseif (isset($map[$parentId])) {
                $map[$parentId]['children'][] = &$node;
            } else {
                $roots[] = &$node;
            }
        }
        unset($node);

        return $roots;
    }

    public function createLocation(string $tenantId, array $data): WarehouseLocation
    {
        return DB::transaction(function () use ($tenantId, $data): WarehouseLocation {
            $now = now();
            $location = new WarehouseLocation(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                warehouseId: $data['warehouse_id'],
                parentId: $data['parent_id'] ?? null,
                name: $data['name'],
                code: $data['code'],
                path: $data['path'],
                level: (int) ($data['level'] ?? 0),
                type: $data['type'],
                isActive: (bool) ($data['is_active'] ?? true),
                createdAt: $now,
                updatedAt: $now,
            );
            $this->locationRepository->save($location);
            Event::dispatch(new WarehouseLocationCreated($location));
            return $location;
        });
    }

    public function updateLocation(string $tenantId, string $id, array $data): WarehouseLocation
    {
        return DB::transaction(function () use ($tenantId, $id, $data): WarehouseLocation {
            $existing = $this->getLocation($tenantId, $id);
            $location = new WarehouseLocation(
                id: $existing->id,
                tenantId: $existing->tenantId,
                warehouseId: $data['warehouse_id'] ?? $existing->warehouseId,
                parentId: array_key_exists('parent_id', $data) ? $data['parent_id'] : $existing->parentId,
                name: $data['name'] ?? $existing->name,
                code: $data['code'] ?? $existing->code,
                path: $data['path'] ?? $existing->path,
                level: isset($data['level']) ? (int) $data['level'] : $existing->level,
                type: $data['type'] ?? $existing->type,
                isActive: isset($data['is_active']) ? (bool) $data['is_active'] : $existing->isActive,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );
            $this->locationRepository->save($location);
            Event::dispatch(new WarehouseLocationUpdated($location));
            return $location;
        });
    }

    public function deleteLocation(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getLocation($tenantId, $id);
            $this->locationRepository->delete($tenantId, $id);
        });
    }
}
