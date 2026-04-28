<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\DTOs\UpdateWarehouseLocationDTO;
use Modules\Warehouse\Application\Services\Concerns\BuildsLocationPath;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;

class UpdateWarehouseLocationService extends BaseService implements UpdateWarehouseLocationServiceInterface
{
    use BuildsLocationPath;

    public function __construct(
        private readonly WarehouseLocationRepositoryInterface $warehouseLocationRepository,
        private readonly WarehouseRepositoryInterface $warehouseRepository,
    )
    {
        parent::__construct($warehouseLocationRepository);
    }

    protected function handle(array $data): WarehouseLocation
    {
        $dto = new UpdateWarehouseLocationDTO(
            id: (int) $data['id'],
            tenantId: (int) $data['tenant_id'],
            warehouseId: (int) $data['warehouse_id'],
            orgUnitId: null,
            parentId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            name: (string) $data['name'],
            code: $data['code'] ?? null,
            type: (string) ($data['type'] ?? 'bin'),
            isActive: (bool) ($data['is_active'] ?? true),
            isPickable: (bool) ($data['is_pickable'] ?? true),
            isReceivable: (bool) ($data['is_receivable'] ?? true),
            capacity: isset($data['capacity']) ? (string) $data['capacity'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        $location = $this->warehouseLocationRepository->find($dto->id);

        if (! $location instanceof WarehouseLocation || $location->getTenantId() !== $dto->tenantId || $location->getWarehouseId() !== $dto->warehouseId) {
            throw new NotFoundException('Warehouse location', $dto->id);
        }

        $warehouse = $this->warehouseRepository->find($dto->warehouseId);
        if (! $warehouse instanceof Warehouse || $warehouse->getTenantId() !== $dto->tenantId) {
            throw new NotFoundException('Warehouse', $dto->warehouseId);
        }

        $dto = new UpdateWarehouseLocationDTO(
            id: $dto->id,
            tenantId: $dto->tenantId,
            warehouseId: $dto->warehouseId,
            orgUnitId: $warehouse->getOrgUnitId(),
            parentId: $dto->parentId,
            name: $dto->name,
            code: $dto->code,
            type: $dto->type,
            isActive: $dto->isActive,
            isPickable: $dto->isPickable,
            isReceivable: $dto->isReceivable,
            capacity: $dto->capacity,
            metadata: $dto->metadata,
        );

        if ($dto->parentId === $dto->id) {
            throw new \InvalidArgumentException('A location cannot be its own parent.');
        }

        $parent = null;
        if ($dto->parentId !== null) {
            $parent = $this->warehouseLocationRepository->find($dto->parentId);

            if (! $parent instanceof WarehouseLocation || $parent->getTenantId() !== $dto->tenantId || $parent->getWarehouseId() !== $dto->warehouseId) {
                throw new NotFoundException('Parent warehouse location', $dto->parentId);
            }

            $currentPath = $location->getPath();
            $parentPath = $parent->getPath();
            if ($currentPath !== null && $parentPath !== null && str_starts_with($parentPath.'/', $currentPath.'/')) {
                throw new \InvalidArgumentException('A location cannot be moved under its own descendant.');
            }
        }

        $oldPath = $location->getPath();
        $newPath = $this->buildLocationPath($parent?->getPath(), $dto->code, $dto->name);
        $newDepth = $parent !== null ? $parent->getDepth() + 1 : 0;

        $location->update(
            name: $dto->name,
            type: $dto->type,
            orgUnitId: $dto->orgUnitId,
            parentId: $dto->parentId,
            code: $dto->code,
            path: $newPath,
            depth: $newDepth,
            isActive: $dto->isActive,
            isPickable: $dto->isPickable,
            isReceivable: $dto->isReceivable,
            capacity: $dto->capacity,
            metadata: $dto->metadata,
        );

        $updatedLocation = $this->warehouseLocationRepository->save($location);

        if ($oldPath !== null && $newPath !== $oldPath) {
            $this->warehouseLocationRepository->updateDescendantPaths(
                tenantId: $dto->tenantId,
                warehouseId: $dto->warehouseId,
                oldPrefix: $oldPath,
                newPrefix: $newPath,
            );
        }

        return $updatedLocation;
    }
}
