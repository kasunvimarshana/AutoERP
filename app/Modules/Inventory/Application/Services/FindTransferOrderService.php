<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\FindTransferOrderServiceInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\TransferOrderRepositoryInterface;

class FindTransferOrderService implements FindTransferOrderServiceInterface
{
    public function __construct(private readonly TransferOrderRepositoryInterface $transferOrderRepository) {}

    public function find(int $tenantId, int $transferOrderId): mixed
    {
        return $this->transferOrderRepository->findById($tenantId, $transferOrderId);
    }

    public function list(int $tenantId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->transferOrderRepository->paginate($tenantId, $perPage, $page);
    }
}
