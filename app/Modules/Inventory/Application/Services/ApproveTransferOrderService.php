<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Inventory\Application\Contracts\ApproveTransferOrderServiceInterface;
use Modules\Inventory\Domain\Entities\TransferOrder;
use Modules\Inventory\Domain\RepositoryInterfaces\TransferOrderRepositoryInterface;

class ApproveTransferOrderService implements ApproveTransferOrderServiceInterface
{
    public function __construct(private readonly TransferOrderRepositoryInterface $transferOrderRepository) {}

    public function execute(int $tenantId, int $transferOrderId): TransferOrder
    {
        $order = $this->transferOrderRepository->markAsApproved($tenantId, $transferOrderId);
        if ($order === null) {
            throw new NotFoundException('TransferOrder', $transferOrderId);
        }

        return $order;
    }
}
