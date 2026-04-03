<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\FindInventoryBatchServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryBatch;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryBatchRepositoryInterface;

class FindInventoryBatchService extends BaseService implements FindInventoryBatchServiceInterface
{
    public function __construct(private readonly InventoryBatchRepositoryInterface $batchRepository)
    {
        parent::__construct($batchRepository);
    }

    public function findByProduct(int $tenantId, int $productId): Collection
    {
        return $this->batchRepository->findByProduct($tenantId, $productId);
    }

    public function findActiveBatches(int $tenantId, int $productId): Collection
    {
        return $this->batchRepository->findActiveBatches($tenantId, $productId);
    }

    public function findByBatchNumber(int $tenantId, string $batchNumber, int $productId): ?InventoryBatch
    {
        return $this->batchRepository->findByBatchNumber($tenantId, $batchNumber, $productId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
