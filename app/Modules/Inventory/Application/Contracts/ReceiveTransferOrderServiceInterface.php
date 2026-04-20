<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\TransferOrder;

interface ReceiveTransferOrderServiceInterface
{
    /**
     * @param  array<int, array{line_id:int, received_qty:string}>  $receivedLines
     */
    public function execute(int $tenantId, int $transferOrderId, array $receivedLines): TransferOrder;
}
