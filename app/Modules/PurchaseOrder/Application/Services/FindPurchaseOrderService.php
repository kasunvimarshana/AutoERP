<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\PurchaseOrder\Application\Contracts\FindPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;

class FindPurchaseOrderService extends BaseService implements FindPurchaseOrderServiceInterface
{
    public function __construct(private readonly PurchaseOrderRepositoryInterface $orderRepository)
    {
        parent::__construct($orderRepository);
    }

    public function findBySupplier(int $tenantId, int $supplierId): Collection
    {
        return $this->orderRepository->findBySupplier($tenantId, $supplierId);
    }

    public function findByStatus(int $tenantId, string $status): Collection
    {
        return $this->orderRepository->findByStatus($tenantId, $status);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
