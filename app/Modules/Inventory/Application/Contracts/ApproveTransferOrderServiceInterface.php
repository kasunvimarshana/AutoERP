<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\TransferOrder;

interface ApproveTransferOrderServiceInterface
{
    public function execute(int $tenantId, int $transferOrderId): TransferOrder;
}
