<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\FindInventoryLevelServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

class FindInventoryLevelService extends BaseService implements FindInventoryLevelServiceInterface
{
    public function __construct(private readonly InventoryLevelRepositoryInterface $levelRepository)
    {
        parent::__construct($levelRepository);
    }

    public function findByProduct(int $tenantId, int $productId): Collection
    {
        return $this->levelRepository->findByProduct($tenantId, $productId);
    }

    public function findByProductAndLocation(int $tenantId, int $productId, ?int $locationId, ?int $batchId): ?InventoryLevel
    {
        return $this->levelRepository->findByProductAndLocation($tenantId, $productId, $locationId, $batchId);
    }

    public function getTotalStock(int $tenantId, int $productId): float
    {
        return $this->levelRepository->getTotalStock($tenantId, $productId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
