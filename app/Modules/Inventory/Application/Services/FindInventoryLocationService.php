<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\FindInventoryLocationServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryLocation;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLocationRepositoryInterface;

class FindInventoryLocationService extends BaseService implements FindInventoryLocationServiceInterface
{
    public function __construct(private readonly InventoryLocationRepositoryInterface $locationRepository)
    {
        parent::__construct($locationRepository);
    }

    public function findByWarehouse(int $tenantId, int $warehouseId): Collection
    {
        return $this->locationRepository->findByWarehouse($tenantId, $warehouseId);
    }

    public function findByCode(int $tenantId, string $code): ?InventoryLocation
    {
        return $this->locationRepository->findByCode($tenantId, $code);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
