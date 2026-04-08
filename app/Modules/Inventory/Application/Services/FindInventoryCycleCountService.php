<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\FindInventoryCycleCountServiceInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;

class FindInventoryCycleCountService extends BaseService implements FindInventoryCycleCountServiceInterface
{
    public function __construct(private readonly InventoryCycleCountRepositoryInterface $cycleCountRepository)
    {
        parent::__construct($cycleCountRepository);
    }

    public function findByWarehouse(int $tenantId, int $warehouseId): Collection
    {
        return $this->cycleCountRepository->findByWarehouse($tenantId, $warehouseId);
    }

    public function findByStatus(int $tenantId, string $status): Collection
    {
        return $this->cycleCountRepository->findByStatus($tenantId, $status);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
