<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\TransferOrder;

interface TransferOrderRepositoryInterface
{
    public function create(TransferOrder $transferOrder): TransferOrder;

    public function findById(int $tenantId, int $transferOrderId): ?TransferOrder;

    public function paginate(int $tenantId, int $perPage, int $page): mixed;

    /**
     * @param  array<int, array{line_id:int, received_qty:string}>  $receivedLines
     */
    public function markAsReceived(int $tenantId, int $transferOrderId, array $receivedLines, string $receivedDate): ?TransferOrder;

    public function markAsApproved(int $tenantId, int $transferOrderId): ?TransferOrder;
}
