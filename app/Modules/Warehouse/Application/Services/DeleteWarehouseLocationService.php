<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseLocationServiceInterface;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;

class DeleteWarehouseLocationService extends BaseService implements DeleteWarehouseLocationServiceInterface
{
    public function __construct(private readonly WarehouseLocationRepositoryInterface $warehouseLocationRepository)
    {
        parent::__construct($warehouseLocationRepository);
    }

    protected function handle(array $data): bool
    {
        $location = $this->warehouseLocationRepository->find((int) $data['id']);

        if (! $location instanceof WarehouseLocation) {
            return false;
        }

        $tenantId = isset($data['tenant_id']) ? (int) $data['tenant_id'] : null;
        if ($tenantId !== null && $location->getTenantId() !== $tenantId) {
            return false;
        }

        $warehouseId = isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null;
        if ($warehouseId !== null && $location->getWarehouseId() !== $warehouseId) {
            return false;
        }

        $locationPath = $location->getPath();
        if ($locationPath !== null) {
            foreach ($this->warehouseLocationRepository->listByWarehouse($location->getTenantId(), $location->getWarehouseId()) as $warehouseLocation) {
                if ($warehouseLocation->getId() === $location->getId()) {
                    continue;
                }

                if ($warehouseLocation->getPath() !== null && str_starts_with($warehouseLocation->getPath().'/', $locationPath.'/')) {
                    throw new \InvalidArgumentException('Delete child locations before deleting this location.');
                }
            }
        }

        return $this->warehouseLocationRepository->delete((int) $data['id']);
    }
}
