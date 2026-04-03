<?php

declare(strict_types=1);

namespace Modules\Dispatch\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\Dispatch\Application\Contracts\FindDispatchServiceInterface;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;

class FindDispatchService extends BaseService implements FindDispatchServiceInterface
{
    public function __construct(private readonly DispatchRepositoryInterface $dispatchRepository)
    {
        parent::__construct($dispatchRepository);
    }

    public function findByWarehouse(int $tenantId, int $warehouseId): Collection
    {
        return $this->dispatchRepository->findByWarehouse($tenantId, $warehouseId);
    }

    public function findByStatus(int $tenantId, string $status): Collection
    {
        return $this->dispatchRepository->findByStatus($tenantId, $status);
    }

    public function findBySalesOrder(int $tenantId, int $salesOrderId): Collection
    {
        return $this->dispatchRepository->findBySalesOrder($tenantId, $salesOrderId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
